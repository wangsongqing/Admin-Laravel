import request from './request'

// 手机号 + 密码登录，返回 OAuth {access_token, refresh_token, expires_in}
export function login(data) {
  return request({ url: '/auth/login', method: 'post', data })
}

// 当前用户信息 + 角色 + 权限列表
export function me() {
  return request({ url: '/auth/me', method: 'get' })
}

// 退出登录
export function logout() {
  return request({ url: '/auth/logout', method: 'post' })
}

// 刷新 token
export function refreshToken(refresh_token) {
  return request({ url: '/auth/refresh', method: 'post', data: { refresh_token } })
}
