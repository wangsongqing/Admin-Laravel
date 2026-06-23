import request from './request'

// 用户列表（分页 + 关键词搜索）
export function getUserList(params) {
  return request({ url: '/users', method: 'get', params })
}

// 可选角色字典（供编辑界面下拉）
export function getRoleOptions() {
  return request({ url: '/users/role-options', method: 'get' })
}

// 新建用户
export function createUser(data) {
  return request({ url: '/users', method: 'post', data })
}

// 更新用户（资料 + 角色）
export function updateUser(id, data) {
  return request({ url: `/users/${id}`, method: 'put', data })
}
