<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * 种子入口：委托给 PermissionSeeder（权限 / 角色 / 超管账号）。
 * 保留此类是为了让 `php artisan db:seed` 与 `migrate:fresh --seed` 按默认流程工作；
 * 权限的实际定义在 config/rbac.php，逻辑在 PermissionSeeder。
 * 后续新增其它种子（如演示数据）可在此 $this->call 追加。
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
        ]);
    }
}
