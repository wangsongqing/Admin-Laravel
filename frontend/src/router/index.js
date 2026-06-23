import { createRouter, createWebHistory } from 'vue-router'
import Layout from '@/layout/index.vue'
import { useUserStore } from '@/stores/user'

export const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/login/index.vue'),
    meta: { hidden: true },
  },
  {
    path: '/',
    component: Layout,
    redirect: '/dashboard',
    children: [
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: () => import('@/views/dashboard/index.vue'),
        meta: { title: '仪表盘', icon: 'Odometer' },
      },
    ],
  },
  {
    path: '/system',
    component: Layout,
    redirect: '/system/user',
    meta: { title: '系统设置', icon: 'Setting' },
    children: [
      {
        path: 'user',
        name: 'SystemUser',
        component: () => import('@/views/system/user/index.vue'),
        meta: { title: '用户管理', icon: 'User', permission: 'system_user_read' },
      },
      {
        path: 'role',
        name: 'SystemRole',
        component: () => import('@/views/system/role/index.vue'),
        meta: { title: '角色管理', icon: 'UserFilled', permission: 'system_role_read' },
      },
    ],
  },
  {
    path: '/403',
    name: 'Forbidden',
    component: () => import('@/views/error/403.vue'),
    meta: { hidden: true },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('@/views/error/404.vue'),
    meta: { hidden: true },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  document.title = to.meta?.title ? `${to.meta.title} - 后台管理` : '后台管理'
  const userStore = useUserStore()

  // 未登录：仅放行登录页
  if (!userStore.accessToken) {
    return to.path === '/login' ? true : `/login?redirect=${to.path}`
  }

  // 刷新页面场景：有 token 但无用户信息，先拉 /me
  if (!userStore.userInfo?.id) {
    try {
      await userStore.fetchUserInfo()
    } catch {
      userStore.reset()
      return `/login?redirect=${to.path}`
    }
  }

  // 已登录访问登录页 → 跳首页
  if (to.path === '/login') return '/'

  // 路由级权限校验
  const required = to.meta?.permission
  if (required && !userStore.hasPermission(required)) {
    return '/403'
  }

  return true
})

export default router
