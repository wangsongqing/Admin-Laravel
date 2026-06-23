<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * 种子：权限项 + 超管角色（赋全部权限）+ 超管账号。
     */
    public function run(): void
    {
        // 1) 权限项（guard 必须与 User.guard_name 一致 = api）
        $permissions = [
            'system_user_read',   // 查看用户列表
            'system_user_write',  // 用户增删改
            'system_role_read',   // 查看角色列表
            'system_role_write',  // 角色增删改 + 分配权限
        ];
        foreach ($permissions as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'api']
            );
        }

        // 2) 超管角色，赋全部权限
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
