<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * 角色数据访问：基于 spatie/permission 的 Role 模型。
 * 业务编排放在 RoleService，这里只做查询/持久化。
 * 所有角色操作限定 guard_name=api，与 User::$guard_name 一致。
 */
class RoleRepository extends BaseRepository
{
    protected function modelClass(): string
    {
        return Role::class;
    }

    /**
     * 分页 + 关键词搜索，预加载权限避免 N+1，按 id 倒序。
     * 仅取 api guard 下的角色。
     */
    public function paginateWithPermissions(int $page, int $perPage, ?string $keyword): LengthAwarePaginator
    {
        return $this->query()
            ->where('guard_name', 'api')
            ->when(filled($keyword), function ($q) use ($keyword) {
                $q->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', '%'.$keyword.'%');
                });
            })
            ->with('permissions:id,name')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * 按 id 取单个角色（限 api guard），不存在返回 null。
     */
    public function findRoleById(int $id): ?Role
    {
        return $this->query()
            ->where('guard_name', 'api')
            ->find($id);
    }

    /**
     * 角色名是否已被占用（限 api guard）。排除自身用于更新场景。
     */
    public function existsByName(string $name, ?int $excludeId = null): bool
    {
        return $this->query()
            ->where('guard_name', 'api')
            ->where('name', $name)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    /**
     * 当前仍被赋予该角色的用户数（用于删除前保护）。
     */
    public function countUsersByRole(int $roleId): int
    {
        return \DB::table('model_has_roles')
            ->where('role_id', $roleId)
            ->count();
    }

    /**
     * 全部可选权限字典（限 api guard），供角色编辑界面勾选。
     *
     * @return Collection<int, Permission>
     */
    public function allPermissions(): Collection
    {
        return Permission::where('guard_name', 'api')
            ->orderBy('id')
            ->get(['id', 'name']);
    }

    /**
     * 全部角色字典（限 api guard），供用户编辑界面下拉选择。
     *
     * @return Collection<int, Role>
     */
    public function allRoles(): Collection
    {
        return $this->query()
            ->where('guard_name', 'api')
            ->orderBy('id')
            ->get(['id', 'name']);
    }
}
