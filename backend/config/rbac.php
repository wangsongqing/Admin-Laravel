<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 系统权限项
    |--------------------------------------------------------------------------
    | 权限名（guard = api）。PermissionSeeder 会据此创建权限，并把全部权限
    | 赋给 admin 角色。
    |
    | 新增功能模块权限的三步：
    |   ① 在此追加权限名 → 重跑 `php artisan db:seed`（幂等）
    |   ② 路由挂 `role_or_permission:xxx`
    |   ③ 前端路由 `meta.permission` + 按钮 `v-permission`
    | 后端无超管兜底，三处缺一会让 admin 都进不去新页面。
    */
    'permissions' => [
        'system_user_read',   // 查看用户列表
        'system_user_write',  // 用户增删改
        'system_role_read',   // 查看角色列表
        'system_role_write',  // 角色增删改 + 分配权限
    ],

];
