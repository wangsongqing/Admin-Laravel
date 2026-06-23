<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService,
    ) {
    }

    /**
     * 用户列表（分页 + 关键词搜索）。示例业务模块。
     * Controller 只做 HTTP 编排 + 调用 Service，不直接碰 Eloquent。
     */
    public function index(Request $request)
    {
        return $this->success($this->userService->listUsers($request));
    }
}
