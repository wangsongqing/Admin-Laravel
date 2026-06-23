<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * 用户业务服务：编排 UserRepository 查询 + spatie 角色同步 + UserResource 序列化 + 分页结构组装。
 * Controller 只调用本服务，不直接碰 Eloquent。
 */
class UserService
{
    public function __construct(
        protected UserRepository $userRepository,
    ) {
    }

    /**
     * 用户列表：分页 + 关键词搜索，组装成前端需要的 {list, total, page, pageSize}。
     */
    public function listUsers(Request $request): array
    {
        $page = (int) $request->input('page', 1);
        $pageSize = (int) $request->input('pageSize', 10);
        $keyword = $request->input('keyword');

        $paginator = $this->userRepository->paginateWithSearch($page, $pageSize, $keyword);

        return [
            'list' => UserResource::collection($paginator->items()),
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'pageSize' => $paginator->perPage(),
        ];
    }

    /**
     * 新建用户。手机号 / 邮箱唯一校验，密码由 User 模型 cast 自动 hash，可同步分配角色。
     *
     * @param array{name: string, phone: string, email?: ?string, password: string, roleIds?: int[]} $data
     */
    public function createUser(array $data): User
    {
        $phone = trim((string) $data['phone']);
        $email = trim((string) ($data['email'] ?? ''));

        if ($this->userRepository->existsByPhone($phone)) {
            throw ValidationException::withMessages(['phone' => '手机号已被占用']);
        }
        if ($this->userRepository->existsByEmail($email)) {
            throw ValidationException::withMessages(['email' => '邮箱已被占用']);
        }

        return DB::transaction(function () use ($data, $phone, $email) {
            /** @var User $user */
            $user = $this->userRepository->create([
                'name'     => trim((string) $data['name']),
                'phone'    => $phone,
                'email'    => $email !== '' ? $email : null,
                'password' => $data['password'],
            ]);

            if (!empty($data['roleIds'])) {
                $user->syncRoles($data['roleIds']);
            }

            return $user->fresh('roles');
        });
    }

    /**
     * 更新用户资料与角色。手机号 / 邮箱唯一（排除自身）；密码留空表示不改；roleIds 传空数组表示清空角色。
     *
     * @param array{name: string, phone: string, email?: ?string, password?: ?string, roleIds?: int[]} $data
     */
    public function updateUser(int $id, array $data): User
    {
        $user = $this->userRepository->findById($id);
        if (!$user) {
            throw ValidationException::withMessages(['user' => '用户不存在']);
        }

        $phone = trim((string) $data['phone']);
        $email = trim((string) ($data['email'] ?? ''));

        if ($this->userRepository->existsByPhone($phone, $id)) {
            throw ValidationException::withMessages(['phone' => '手机号已被占用']);
        }
        if ($this->userRepository->existsByEmail($email, $id)) {
            throw ValidationException::withMessages(['email' => '邮箱已被占用']);
        }

        return DB::transaction(function () use ($id, $data, $phone, $email) {
            $update = [
                'name'  => trim((string) $data['name']),
                'phone' => $phone,
                'email' => $email !== '' ? $email : null,
            ];
            // 密码为空表示不改，避免空串被 hash
            if (!empty($data['password'])) {
                $update['password'] = $data['password'];
            }
            $this->userRepository->update($id, $update);

            $user = $this->userRepository->findById($id);
            if (array_key_exists('roleIds', $data)) {
                $user->syncRoles($data['roleIds']);
            }

            return $user->fresh('roles');
        });
    }
}
