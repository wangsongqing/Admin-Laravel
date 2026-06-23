import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { login as loginApi, me as meApi, logout as logoutApi } from '@/api/auth'
import {
  getAccessToken,
  setAccessToken,
  getRefreshToken,
  setRefreshToken,
  removeTokens,
} from '@/utils/auth'

export const useUserStore = defineStore('user', () => {
  const accessToken = ref(getAccessToken() || '')
  const refreshToken = ref(getRefreshToken() || '')
  const userInfo = ref({})
  const roles = ref([])
  const permissions = ref([])

  const isLogin = computed(() => !!accessToken.value)
  // admin 角色兜底视为拥有全部权限
  const isSuperAdmin = computed(() => roles.value.includes('admin'))

  /**
   * 是否拥有某权限；admin 角色或权限命中即放行。
   */
  function hasPermission(perm) {
    if (!perm) return true
    return isSuperAdmin.value || permissions.value.includes(perm)
  }

  /**
   * 登录：拿 OAuth token → 拉取用户信息与权限。
   */
  async function login(loginForm) {
    const data = await loginApi(loginForm)
    accessToken.value = data.access_token
    refreshToken.value = data.refresh_token
    setAccessToken(data.access_token)
    setRefreshToken(data.refresh_token)
    await fetchUserInfo()
  }

  async function fetchUserInfo() {
    const data = await meApi()
    userInfo.value = {
      id: data.id,
      name: data.name,
      phone: data.phone,
      email: data.email,
    }
    roles.value = data.roles || []
    permissions.value = data.permissions || []
    return data
  }

  async function logout() {
    try {
      await logoutApi()
    } finally {
      reset()
    }
  }

  function reset() {
    accessToken.value = ''
    refreshToken.value = ''
    userInfo.value = {}
    roles.value = []
    permissions.value = []
    removeTokens()
  }

  return {
    accessToken,
    refreshToken,
    userInfo,
    roles,
    permissions,
    isLogin,
    isSuperAdmin,
    hasPermission,
    login,
    fetchUserInfo,
    logout,
    reset,
  }
})
