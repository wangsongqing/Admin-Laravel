<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Passport 12 默认关闭了 password grant（OAuth2.1 已弃用），
        // 照搬原项目用手机号+密码换 token 的模式，需显式开启。
        Passport::enablePasswordGrant();

        // token / refresh_token 统一在「北京次日凌晨 4 点」过期
        $beijingTime = Carbon::now()
            ->setTimezone('Asia/Shanghai')
            ->tomorrow('Asia/Shanghai')
            ->addHours(4)
            ->toDateTimeString();

        $expiresAt = Carbon::createFromFormat('Y-m-d H:i:s', $beijingTime, 'Asia/Shanghai')
            ->setTimezone('UTC');

        Passport::tokensExpireIn($expiresAt);
        Passport::refreshTokensExpireIn($expiresAt);
        Passport::personalAccessTokensExpireIn($expiresAt);
    }
}
