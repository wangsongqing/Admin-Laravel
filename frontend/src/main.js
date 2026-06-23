import { createApp } from 'vue'
import { createPinia } from 'pinia'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import * as ElementPlusIconsVue from '@element-plus/icons-vue'
import zhCn from 'element-plus/es/locale/lang/zh-cn'

import App from './App.vue'
import router from './router'
import { useUserStore } from './stores/user'
import './style.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.use(ElementPlus, { locale: zhCn })

// 全局注册所有 Element Plus 图标
for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
  app.component(key, component)
}

// v-permission 指令：按钮级权限控制
// 用法：<el-button v-permission="'system_user_write'">新增</el-button>
app.directive('permission', {
  mounted(el, binding) {
    const userStore = useUserStore()
    const required = binding.value
    const perms = Array.isArray(required) ? required : [required]
    const ok = perms.some((p) => userStore.hasPermission(p))
    if (!ok && el.parentNode) {
      el.parentNode.removeChild(el)
    }
  },
})

app.mount('#app')
