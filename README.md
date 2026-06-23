# 后台管理系统（Vue 3 + Laravel 10）

前后端分离的后台管理系统：
- **后端** `backend/`：Laravel 10（API only），**Laravel Passport**（OAuth2 Password Grant）认证 + **spatie/laravel-permission** RBAC 权限
- **前端** `frontend/`：Vue 3 + Vite + Element Plus + Pinia + Vue Router
- 登录方式：**手机号 + 密码**（照搬 `/Users/songsong/development/PHP/backend` 的方案）

## 技术栈

| 层 | 选型 |
|---|---|
| 后端 | Laravel 10.x（PHP 8.1+）|
| 认证 | laravel/passport 12（OAuth2 password grant，手机号换 token）|
| 权限 | spatie/laravel-permission 6（角色/权限 + `role_or_permission` 中间件）|
| 数据库 | MySQL（库 `ai_test`，127.0.0.1，root/root）|
| 前端 | Vue 3（组合式 API）+ Vite 8 |
| UI | Element Plus + @element-plus/icons-vue |
| 状态/路由 | Pinia + Vue Router |
| HTTP | axios（拦截器统一注入 access_token / 处理 401/403）|

## 目录结构

```
Admin/
├── backend/
│   ├── app/Http/Controllers/Api/V1/   AuthController(继承 AccessTokenController) · UserController（仅调 Service）
│   ├── app/Services/                  AuthService(发/刷/撤销 token + profile) · UserService(用户业务)
│   ├── app/Repositories/              BaseRepository 基类 · UserRepository · AccessTokenRepository（唯一碰 Eloquent/DB 的层）
│   ├── app/Http/Responses/            JsonResponseTrait 统一响应
│   ├── app/Http/Middleware/           RoleOrPermissionMiddleware（继承 spatie）
│   ├── app/Models/User.php            phone 登录 + HasRoles + guard_name=api
│   ├── app/Providers/AuthServiceProvider.php  Passport::enablePasswordGrant() + token 北京4点过期
│   ├── config/auth.php                api guard = passport
│   ├── routes/api.php                 /api/auth/* + /api/users（带权限中间件）
│   └── database/seeders/              权限 + admin 角色 + 超管账号
└── frontend/
    └── src/
        ├── api/      request.js(拦截器) + auth.js + user.js
        ├── stores/   user.js(token/权限/hasPermission) + app.js
        ├── router/   路由 + beforeEach 守卫（含权限校验 + /me 水合）
        ├── layout/   Sidebar(按权限过滤菜单) / Navbar / AppMain
        └── views/    login(手机号) / dashboard / system/user / error/{403,404}
```

## 快速开始

### 0. 前置
- PHP 8.1+（扩展：pdo_mysql / mbstring / fileinfo / openssl）、Composer
- Node 18+、npm
- 本地 MySQL（已建库 `ai_test`，账号 root / 密码 root）

### 1. 启动后端（:8000）
```bash
cd backend
php artisan serve --host=127.0.0.1 --port=8000
```
> 数据库、Passport 密钥、password client、权限/角色/账号均已 migrate + seed 完成。

如需完全重置：
```bash
php artisan migrate:fresh --seed
php artisan passport:install --no-interaction   # 重新生成密钥与 password client
```

### 2. 启动前端（:5173）
```bash
cd frontend
npm install        # 首次
npm run dev
```
浏览器打开 http://localhost:5173 ，开发期 `/api` 由 Vite proxy 转发到 `http://127.0.0.1:8000`。

### 3. 账号
| 手机号 | 密码 | 角色 | 能看到/操作 |
|---|---|---|---|
| `13800138000` | `password` | admin（全部权限）| 全部菜单 + 「新增用户」按钮 |
| `13900139000` | `password` | 无 | 无业务菜单（演示权限隔离）|

## 鉴权流程
```
登录  POST /api/auth/login {phone, password}
      ← {token_type, expires_in, access_token, refresh_token}   # 标准 OAuth2 响应
      → access_token 存 localStorage，axios 自动带 Authorization: Bearer <access_token>
      → 前端再 GET /api/auth/me 拉取 用户信息 + roles + permissions
权限  路由 meta.permission + store.hasPermission() 控制菜单/按钮；v-permission 指令做按钮级
路由  后端 role_or_permission:xxx 中间件校验，无权限返回 403
刷新  POST /api/auth/refresh {refresh_token}（接口已提供；前端按需接入自动刷新）
退出  POST /api/auth/logout → 撤销该用户所有 token → 清前端状态 → 跳 /login
过期  token 统一到「北京次日凌晨 4 点」失效
401   非登录接口 401 → 拦截器清 token 跳登录；登录接口 400/401 → 提示「账号或密码错误」
```

## 响应格式
- **业务接口**统一信封：`{ "code": 0, "message": "success", "data": {...} }`，前端拦截器自动解包 `data`。
- **登录/刷新**返回的是**原始 OAuth2 响应**（非信封），拦截器按「无 code+data」识别后原样返回。

## 主要 API
| 方法 | 路径 | 鉴权 | 权限 | 说明 |
|---|---|---|---|---|
| POST | /api/auth/login | 否 | - | 手机号+密码换 access_token/refresh_token |
| POST | /api/auth/refresh | 否 | - | refresh_token 换新 token |
| GET  | /api/auth/me | 是 | - | 当前用户 + roles + permissions |
| POST | /api/auth/logout | 是 | - | 撤销当前用户所有 token |
| GET  | /api/users | 是 | `system_user_read` | 用户列表（分页/搜索）|

## 权限模型（核心 RBAC）
- 权限项（`permissions` 表，guard=`api`）：`system_user_read`、`system_user_write`（示例，按业务在 seeder 扩充）
- 角色（`roles` 表）：`admin`（赋全部权限）
- 关系：User ↔ Role（`model_has_roles`）、Role ↔ Permission（`role_has_permissions`）
- `User.guard_name = 'api'` 必须与角色/权限的 `guard_name` 一致，否则校验失效
- 加新模块权限：① seeder 里加 permission；② 路由挂 `role_or_permission:xxx`；③ 前端路由 `meta.permission` + 按钮 `v-permission`

## 关键改动记录
- 认证由 Sanctum 改为 **Passport**；登录字段由 email 改为 **phone**。
- **Passport 12 默认关闭 password grant**，必须在 `AuthServiceProvider::boot` 调 `Passport::enablePasswordGrant()`，否则登录报 `unsupported_grant_type`。
- 纯 API 后端的 401 需覆写 `app/Exceptions/Handler::unauthenticated()` 直接返回 401 JSON。

## 生产部署提示
- 后端 `.env` 关掉 `APP_DEBUG`，配真实数据库与 `APP_URL`；Passport 密钥（`storage/oauth-*.key`）需部署。
- 前端 `npm run build` 产出 `frontend/dist/`，由 Nginx 托管，并把 `/api` 反代到后端。
- `config/cors.php` 当前 `allowed_origins: ['*']`（开发友好），生产环境应收紧为真实前端域名。
