<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;

/**
 * 用户业务服务：编排 UserRepository 查询 + UserResource 序列化 + 分页结构组装。
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
}
