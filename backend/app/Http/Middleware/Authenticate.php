<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * 未登录时的跳转地址。
     * 本项目是纯 API 后端（无 web 登录页），返回 null 让框架直接返回 401 JSON，
     * 不再尝试 route('login')（会因路由不存在而抛 RouteNotFoundException）。
     */
    protected function redirectTo(Request $request): ?string
    {
        return null;
    }
}
