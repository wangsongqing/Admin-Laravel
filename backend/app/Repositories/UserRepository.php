<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 用户数据访问：分页搜索、按手机号查询等。
 * 业务逻辑放在 UserService，这里只做查询/持久化。
 */
class UserRepository extends BaseRepository
{
    protected function modelClass(): string
    {
        return User::class;
    }

    /**
     * 分页 + 关键词搜索（name 或 email 模糊匹配），按 id 倒序。
     */
    public function paginateWithSearch(int $page, int $pageSize, ?string $keyword): LengthAwarePaginator
    {
        return $this->query()
            ->when(filled($keyword), function ($q) use ($keyword) {
                // 外层 where 闭包，避免 orWhere 污染后续条件
                $q->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', '%'.$keyword.'%')
                        ->orWhere('phone', 'like', '%'.$keyword.'%')
                        ->orWhere('email', 'like', '%'.$keyword.'%');
                });
            })
            ->with('roles:id,name')
            ->orderByDesc('id')
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * 手机号是否已被占用（可排除自身用于编辑场景）。
     */
    public function existsByPhone(string $phone, ?int $excludeId = null): bool
    {
        return $this->query()
            ->where('phone', $phone)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    /**
     * 邮箱是否已被占用（空值视为未占用，允许多个 null）。
     */
    public function existsByEmail(?string $email, ?int $excludeId = null): bool
    {
        if (blank($email)) {
            return false;
        }

        return $this->query()
            ->where('email', $email)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    /**
     * 按手机号查用户。
     */
    public function findByPhone(string $phone): ?User
    {
        return $this->query()->where('phone', $phone)->first();
    }
}
