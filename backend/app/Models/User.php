<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * Spatie 权限使用的 guard 名称。
     * 必须与建角色/权限时写入的 guard_name 一致，否则权限校验失效。
     */
    protected string $guard_name = 'api';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'status'            => 'boolean',
    ];

    /**
     * Passport 按 phone 字段查找用户（手机号登录）。
     */
    public function findForPassport($login)
    {
        return static::query()->where('phone', $login)->first();
    }

    /**
     * Passport 密码校验（bcrypt）。
     * 停用用户（status=0）直接返回 false，与密码错误合并为同一个 400 响应。
     */
    public function validateForPassportPasswordGrant(string $password): bool
    {
        if (! $this->status) {
            return false;
        }

        if (Hash::check($password, $this->password)) {
            return true;
        }

        return false;
    }

    /**
     * 当前用户拥有的权限 name 列表（给前端做菜单/按钮级控制）。
     */
    public function getPermissionNames(): array
    {
        return $this->getAllPermissions()->pluck('name')->values()->all();
    }
}
