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
                        ->orWhere('email', 'like', '%'.$keyword.'%');
                });
            })
            ->orderByDesc('id')
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * 按手机号查用户。
     */
    public function findByPhone(string $phone): ?User
    {
        return $this->query()->where('phone', $phone)->first();
    }
}
