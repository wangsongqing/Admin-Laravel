<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\RoleService;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected RoleService $roleService,
    ) {
    }

    /**
     * 用户列表（分页 + 关键词搜索）。
     */
    public function index(Request $request)
    {
        return $this->success($this->userService->listUsers($request));
    }

    /**
     * 可选角色字典（供用户编辑界面下拉）。
     */
    public function roleOptions()
    {
        return $this->success($this->roleService->roleOptions());
    }

    /**
     * 新建用户。
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'      => ['required', 'string', 'max:50'],
            'phone'     => ['required', 'regex:/^1\d{10}$/'],
            'email'     => ['nullable', 'email', 'max:100'],
            'password'  => ['required', 'string', 'min:6', 'max:32'],
            'roleIds'   => ['nullable', 'array'],
            'roleIds.*' => ['integer'],
        ], [
            'phone.required' => '手机号不能为空',
            'phone.regex'    => '手机号格式错误',
        ]);

        $user = $this->userService->createUser($request->only('name', 'phone', 'email', 'password', 'roleIds'));

        return $this->success(new UserResource($user), '创建成功');
    }

    /**
     * 更新用户（资料 + 角色）。
     */
    public function update(Request $request, int $id)
    {
        $request->validate([
            'name'      => ['required', 'string', 'max:50'],
            'phone'     => ['required', 'regex:/^1\d{10}$/'],
            'email'     => ['nullable', 'email', 'max:100'],
            'password'  => ['nullable', 'string', 'min:6', 'max:32'],
            'roleIds'   => ['nullable', 'array'],
            'roleIds.*' => ['integer'],
        ], [
            'phone.required' => '手机号不能为空',
            'phone.regex'    => '手机号格式错误',
        ]);

        $user = $this->userService->updateUser($id, $request->only('name', 'phone', 'email', 'password', 'roleIds'));

        return $this->success(new UserResource($user), '更新成功');
    }
}
