import request from './request'

export function getUserList(params) {
  return request({ url: '/users', method: 'get', params })
}
