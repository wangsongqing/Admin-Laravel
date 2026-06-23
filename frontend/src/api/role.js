import request from './request'

// 角色列表（分页 + 关键词搜索）
export function getRoleList(params) {
  return request({ url: '/roles', method: 'get', params })
}

// 可选权限字典（供角色编辑界面渲染复选框）
export function getPermissionList() {
  return request({ url: '/roles/permissions', method: 'get' })
}

// 新建角色
export function createRole(data) {
  return request({ url: '/roles', method: 'post', data })
}

// 更新角色（改名 / 改权限）
export function updateRole(id, data) {
  return request({ url: `/roles/${id}`, method: 'put', data })
}

// 删除角色
export function deleteRole(id) {
  return request({ url: `/roles/${id}`, method: 'delete' })
}
