<?php

namespace App\Http\Middleware;

use Spatie\Permission\Middleware\RoleOrPermissionMiddleware as SpatieRoleOrPermissionMiddleware;

/**
 * 角色/权限校验中间件（直接继承 spatie 同名中间件）。
 * 用法：->middleware('role_or_permission:system_user_read|system_user_write')
 * 参数 `|` 分隔表示「任一命中即放行」。
 */
class RoleOrPermissionMiddleware extends SpatieRoleOrPermissionMiddleware
{
    // 如需自定义校验逻辑可在此重写 handle()
}
