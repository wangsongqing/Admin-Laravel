// Token 本地持久化（Passport access_token / refresh_token），刷新不丢登录
const ACCESS_TOKEN_KEY = 'admin_access_token'
const REFRESH_TOKEN_KEY = 'admin_refresh_token'

export function getAccessToken() {
  return localStorage.getItem(ACCESS_TOKEN_KEY)
}
export function setAccessToken(token) {
  return localStorage.setItem(ACCESS_TOKEN_KEY, token)
}
export function getRefreshToken() {
  return localStorage.getItem(REFRESH_TOKEN_KEY)
}
export function setRefreshToken(token) {
  return localStorage.setItem(REFRESH_TOKEN_KEY, token)
}
export function removeTokens() {
  localStorage.removeItem(ACCESS_TOKEN_KEY)
  localStorage.removeItem(REFRESH_TOKEN_KEY)
}
