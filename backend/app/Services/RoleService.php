<?php

namespace App\Services;

use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Repositories\RoleRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * 角色业务服务：编排 RoleRepository 查询 + spatie 权限同步 + RoleResource 序列化。
 * Controller 只调用本服务，不直接碰 Eloquent。
 *
 * 保护规则：名为 admin 的超级管理员角色禁止改名 / 删除（前端 isSuperAdmin 据此兜底放行全部权限）。
 */
class RoleService
{
    /** 超级管理员角色名，受保护不可改名/删除。 */
    public const SUPER_ADMIN = 'admin';

    public function __construct(
        protected RoleRepository $roleRepository,
    ) {
    }

    /**
     * 角色列表：分页 + 关键词搜索，组装成 {list, total, page, pageSize}。
     * list 项已预加载 permissions。
     */
    public function listRoles(Request $request): array
    {
        $page = (int) $request->input('page', 1);
        $pageSize = (int) $request->input('pageSize', 10);
        $keyword = $request->input('keyword');

        $paginator = $this->roleRepository->paginateWithPermissions($page, $pageSize, $keyword);

        return [
            'list'     => RoleResource::collection($paginator->items()),
            'total'    => $paginator->total(),
            'page'     => $paginator->currentPage(),
            'pageSize' => $paginator->perPage(),
        ];
    }

    /**
     * 全部可选权限字典，供角色编辑界面渲染复选框。
     */
    public function listPermissions(): array
    {
        return [
            'list' => PermissionResource::collection($this->roleRepository->allPermissions()),
        ];
    }

    /**
     * 角色字典（id + name），供用户编辑界面下拉选择。
     */
    public function roleOptions(): array
    {
        return [
            'list' => $this->roleRepository->allRoles()
                ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])
                ->values()
                ->all(),
        ];
    }

    /**
     * 新建角色并同步权限。spatie syncPermissions 会自动清权限缓存。
     *
     * @param array{name: string, permissions?: string[]} $data
     */
    public function createRole(array $data): Role
    {
        $name = trim((string) $data['name']);

        if ($this->roleRepository->existsByName($name)) {
            throw ValidationException::withMessages(['name' => '角色名已存在']);
        }

        return DB::transaction(function () use ($name, $data) {
            /** @var Role $role */
            $role = $this->roleRepository->create([
                'name'       => $name,
                'guard_name' => 'api',
            ]);
            $role->syncPermissions($data['permissions'] ?? []);

            return $role->fresh('permissions');
        });
    }

    /**
     * 更新角色名与权限。admin 角色禁止改名。
     *
     * @param array{name?: string, permissions?: string[]} $data
     */
    public function updateRole(int $id, array $data): Role
    {
        $role = $this->roleRepository->findRoleById($id);
        if (!$role) {
            throw ValidationException::withMessages(['role' => '角色不存在']);
        }

        $newName = isset($data['name']) ? trim((string) $data['name']) : null;

        // 超管角色：保留原名，仅允许改权限
        if ($role->name === self::SUPER_ADMIN && $newName !== null && $newName !== self::SUPER_ADMIN) {
            throw ValidationException::withMessages(['name' => '超级管理员角色不可改名']);
        }

        if ($newName !== null && $newName !== $role->name) {
            if ($this->roleRepository->existsByName($newName, $id)) {
                throw ValidationException::withMessages(['name' => '角色名已存在']);
            }
            $this->roleRepository->update($id, ['name' => $newName]);
        }

        return DB::transaction(function () use ($id, $data) {
            $role = $this->roleRepository->findRoleById($id);
            if (array_key_exists('permissions', $data)) {
                $role->syncPermissions($data['permissions']);
            }

            return $role->fresh('permissions');
        });
    }

    /**
     * 删除角色。admin 角色、仍有用户挂载的角色禁止删除。
     */
    public function deleteRole(int $id): void
    {
        $role = $this->roleRepository->findRoleById($id);
        if (!$role) {
            throw ValidationException::withMessages(['role' => '角色不存在']);
        }

        if ($role->name === self::SUPER_ADMIN) {
            throw ValidationException::withMessages(['role' => '超级管理员角色不可删除']);
        }

        if ($this->roleRepository->countUsersByRole($id) > 0) {
            throw ValidationException::withMessages(['role' => '该角色下仍有用户，无法删除']);
        }

        $this->roleRepository->delete($id);
    }
}
