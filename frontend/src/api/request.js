import axios from 'axios'
import { ElMessage } from 'element-plus'
import { getAccessToken, removeTokens } from '@/utils/auth'

const service = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  timeout: 15000,
})

// 请求拦截器：自动注入 Bearer Token
service.interceptors.request.use(
  (config) => {
    const token = getAccessToken()
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error),
)

// 响应拦截器：区分「统一信封」与「原始响应」，统一处理错误
service.interceptors.response.use(
  (response) => {
    const res = response.data
    // 统一信封 {code, message, data}；否则视为原始响应（如 OAuth token）
    const isEnvelope =
      res && typeof res === 'object' && 'code' in res && 'data' in res
    if (!isEnvelope) return res
    if (res.code === 0) return res.data
    // 业务错误（HTTP 200 但 code 非 0）
    ElMessage.error(res.message || '请求失败')
    return Promise.reject(new Error(res.message || 'Error'))
  },
  (error) => {
    const status = error.response?.status
    const url = error.config?.url || ''
    const isLoginRequest = url.includes('/auth/login')

    // 登录接口：OAuth 凭证错误是 400(invalid_grant) 或 401，统一友好提示、不跳转
    if (isLoginRequest && (status === 400 || status === 401)) {
      ElMessage.error('账号或密码错误')
      return Promise.reject(error)
    }

    if (status === 401) {
      // 其它接口 401 = token 失效，清状态跳登录
      removeTokens()
      ElMessage.error('登录已过期，请重新登录')
      import('@/router').then(({ default: router }) => {
        const redirect = encodeURIComponent(router.currentRoute.value.fullPath)
        router.push(`/login?redirect=${redirect}`)
      })
    } else if (status === 403) {
      ElMessage.error(error.response?.data?.message || '没有访问权限')
    } else if (status === 422) {
      const errs = error.response?.data?.errors
      const first = errs ? Object.values(errs)[0]?.[0] : null
      ElMessage.error(first || error.response?.data?.message || '请求参数错误')
    } else {
      ElMessage.error(error.response?.data?.message || error.message || '请求失败')
    }
    return Promise.reject(error)
  },
)

export default service
