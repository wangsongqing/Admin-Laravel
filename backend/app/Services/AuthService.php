<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\AccessTokenRepository;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 认证服务：封装 Passport password grant 的内部发 token / 刷 token / 撤销 token。
 * 数据访问（oauth_clients / Token 撤销）下沉到 AccessTokenRepository，本类只做编排。
 * 思路照搬 Modules\Base\Services\AuthService（核心 RBAC 去掉了部门/短信逻辑）。
 */
class AuthService
{
    public function __construct(
        protected AccessTokenRepository $accessTokenRepository,
    ) {
    }

    /**
     * 组装 password grant 的 PSR-7 请求，供 AuthController 调 issueToken。
     *
     * @param array $credentials
     *
     * @return ServerRequestInterface
     */
    public function getRequestForToken(array $credentials): ServerRequestInterface
    {
        $client = $this->accessTokenRepository->getPasswordClient();

        $params = [
            'grant_type'    => 'password',
            'client_id'     => $client->id,
            'client_secret' => $client->secret,
            'scope'         => '*',
            'username'      => $credentials['phone'],
            'password'      => $credentials['password'],
        ];

        return app(ServerRequestInterface::class)->withParsedBody($params);
    }

    /**
     * 组装 refresh_token grant 的 PSR-7 请求。
     *
     * @param string $refreshToken
     *
     * @return ServerRequestInterface
     */
    public function getRequestForRefresh(string $refreshToken): ServerRequestInterface
    {
        $client = $this->accessTokenRepository->getPasswordClient();

        $params = [
            'grant_type'    => 'refresh_token',
            'client_id'     => $client->id,
            'client_secret' => $client->secret,
            'scope'         => '*',
            'refresh_token' => $refreshToken,
        ];

        return app(ServerRequestInterface::class)->withParsedBody($params);
    }

    /**
     * 登出：把当前用户所有 access token 标记 revoked。
     *
     * @param int $userId
     *
     * @return int 受影响行数
     */
    public function logOut(int $userId): int
    {
        return $this->accessTokenRepository->revokeAllByUser($userId);
    }

    /**
     * 当前用户资料聚合：基础字段 + 角色名 + 权限名（前端据此做菜单/按钮级控制）。
     */
    public function profile(User $user): array
    {
        return [
            'id'          => $user->id,
            'name'        => $user->name,
            'phone'       => $user->phone,
            'email'       => $user->email,
            'roles'       => $user->getRoleNames()->values(),
            'permissions' => $user->getPermissionNames(),
        ];
    }
}
