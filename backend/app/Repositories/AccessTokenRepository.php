<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Laravel\Passport\Token;
use RuntimeException;

/**
 * OAuth 基础设施数据访问：password client 查询、access token 撤销。
 * 属认证域的基础设施操作，与业务实体 User 分开存放，不混进 UserRepository。
 */
class AccessTokenRepository
{
    /**
     * 取 password_client=1 且未撤销的 OAuth 客户端（登录换 token 用）。
     *
     * @return object
     *
     * @throws RuntimeException 找不到时（需先 passport:install）
     */
    public function getPasswordClient(): object
    {
        $client = DB::table('oauth_clients')
            ->where('password_client', true)
            ->where('revoked', false)
            ->first();

        if (!$client) {
            throw new RuntimeException('未找到 password grant 客户端，请先执行 php artisan passport:install（或 php artisan passport:client --password 单独创建）');
        }

        return $client;
    }

    /**
     * 撤销某用户所有 access token（登出）。返回受影响行数。
     */
    public function revokeAllByUser(int $userId): int
    {
        return Token::query()->where('user_id', $userId)->update(['revoked' => true]);
    }
}
