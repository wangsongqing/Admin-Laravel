<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\JsonResponseTrait;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passport\Http\Controllers\AccessTokenController;

/**
 * 认证控制器：照搬原项目，直接继承 Passport 的 AccessTokenController，
 * 内部以 password grant 发 token，不把 client_secret 暴露给前端。
 * 由于继承的是 Passport 控制器（非我们的 Controller 基类），
 * 这里手动引入 JsonResponseTrait 统一业务响应格式。
 */
class AuthController extends AccessTokenController
{
    use JsonResponseTrait;

    /**
     * 登录：手机号 + 密码，返回 OAuth access_token + refresh_token。
     */
    public function login(Request $request, AuthService $authService)
    {
        $request->validate([
            'phone'    => ['required', 'regex:/^1\d{10}$/'],
            'password' => 'required|string',
        ], [
            'phone.required'    => '手机号不能为空',
            'phone.regex'       => '手机号格式错误',
            'password.required' => '密码不能为空',
        ]);

        $serverRequest = $authService->getRequestForToken($request->only('phone', 'password'));

        // issueToken 返回标准 OAuth2 响应：{token_type, expires_in, access_token, refresh_token}
        return $this->issueToken($serverRequest);
    }

    /**
     * 刷新 token。
     */
    public function refresh(Request $request, AuthService $authService)
    {
        $request->validate([
            'refresh_token' => 'required|string',
        ]);

        return $this->issueToken($authService->getRequestForRefresh($request->input('refresh_token')));
    }

    /**
     * 退出登录：撤销当前用户所有 token。
     */
    public function logout(Request $request, AuthService $authService): JsonResponse
    {
        $authService->logOut($request->user()->id);

        return $this->success(null, '已退出登录');
    }

    /**
     * 当前用户信息 + 角色名 + 权限名列表（前端据此做菜单/按钮级控制）。
     */
    public function me(Request $request, AuthService $authService): JsonResponse
    {
        return $this->success($authService->profile($request->user()));
    }
}
