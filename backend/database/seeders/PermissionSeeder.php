<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * 权限种子：权限项（来源 config/rbac.php）+ 超管角色（赋全部权限）+ 超管账号。
 * 幂等：firstOrCreate / syncPermissions / updateOrCreate，可安全重复执行。
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1) 权限项（guard 必须与 User.guard_name 一致 = api），来源 config/rbac.php
        $permissions = (array) config('rbac.permissions', []);

        foreach ($permissions as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'api']
            );
        }

        // 2) 超管角色，赋全部 api 权限
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $adminRole->syncPermissions(
            Permission::where('guard_name', 'api')->pluck('name')->all()
        );

        // 3) 超管账号（手机号登录）。password cast 会自动 hash。
        $admin = User::updateOrCreate(
            ['phone' => '13800138000'],
            [
                'name' => '超级管理员',
                'password' => 'password',
            ]
        );
        $admin->assignRole($adminRole);
    }
}
