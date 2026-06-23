<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
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

        // 3) 超管账号（手机号登录）。避免不必要的时间戳更新。
        $admin = User::where('phone', '13800138000')->first();

        if ($admin) {
            // 账号已存在，检查密码是否需要更新（避免不必要的属性更新）
            if (Hash::needsRehash($admin->password)) {
                $admin->update(['password' => 'password']);
            }
        } else {
            // 账号不存在，创建新账号
            $admin = User::create([
                'phone' => '13800138000',
                'name' => '超级管理员',
                'password' => 'password',
            ]);
        }

        $admin->assignRole($adminRole);
    }
}
