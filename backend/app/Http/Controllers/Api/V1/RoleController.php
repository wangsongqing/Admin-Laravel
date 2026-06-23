<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\RoleService;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(
        protected RoleService $roleService,
    ) {
    }

    /**
     * 角色列表（分页 + 关键词搜索）。
     */
    public function index(Request $request)
    {
        return $this->success($this->roleService->listRoles($request));
    }

    /**
     * 可选权限字典（供角色编辑界面渲染复选框）。
     */
    public function permissions()
    {
        return $this->success($this->roleService->listPermissions());
    }

    /**
     * 新建角色。
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:50'],
            'permissions'    => ['nullable', 'array'],
            'permissions.*'  => ['string'],
        ]);

        $role = $this->roleService->createRole($request->only('name', 'permissions'));

        return $this->success(new \App\Http\Resources\RoleResource($role), '创建成功');
    }

    /**
     * 更新角色（改名 / 改权限）。
     */
    public function update(Request $request, int $id)
    {
        $request->validate([
            'name'           => ['required', 'string', 'max:50'],
            'permissions'    => ['nullable', 'array'],
            'permissions.*'  => ['string'],
        ]);

        $role = $this->roleService->updateRole($id, $request->only('name', 'permissions'));

        return $this->success(new \App\Http\Resources\RoleResource($role), '更新成功');
    }

    /**
     * 删除角色。
     */
    public function destroy(int $id)
    {
        $this->roleService->deleteRole($id);

        return $this->success(null, '删除成功');
    }
}
