<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| 所有路由自动带 /api 前缀。
|
*/

Route::prefix('auth')->group(function () {
    // 公开
    Route::post('login', [AuthController::class, 'login']);
    Route::post('refresh', [AuthController::class, 'refresh']);

    // 需登录
    Route::middleware('auth:api')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

// 受保护业务路由：auth:api（passport）+ role_or_permission 权限校验

// 用户管理
Route::middleware(['auth:api', 'role_or_permission:system_user_read'])->group(function () {
    Route::get('users', [UserController::class, 'index']);
});

// 角色管理：读
Route::middleware(['auth:api', 'role_or_permission:system_role_read'])->group(function () {
    Route::get('roles', [RoleController::class, 'index']);
    // 字典接口放在 {id} 之前，避免被通配匹配
    Route::get('roles/permissions', [RoleController::class, 'permissions']);
});

// 角色管理：写
Route::middleware(['auth:api', 'role_or_permission:system_role_write'])->group(function () {
    Route::post('roles', [RoleController::class, 'store']);
    Route::put('roles/{id}', [RoleController::class, 'update']);
    Route::delete('roles/{id}', [RoleController::class, 'destroy']);
});
