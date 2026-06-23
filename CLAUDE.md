## 概览

前后端分离的后台管理系统。
- `backend/`：Laravel 10 **API-only**，laravel/passport 12（OAuth2 password grant，手机号换 token）+ spatie/laravel-permission 6（RBAC）。
- `frontend/`：Vue 3（组合式 API）+ Vite 8 + Element Plus + Pinia + Vue Router。
- 登录字段是 **手机号 + 密码**（非 email）。

## 常用命令

后端（`cd backend`）：
- 启动：`php artisan serve --host=127.0.0.1 --port=8000`
- 迁移：`php artisan migrate`；迁移+种子：`php artisan migrate --seed`
- 重跑种子（幂等，权限定义在 `config/rbac.php`，见坑 #1）：`php artisan db:seed`
- 完全重置：`php artisan migrate:fresh --seed && php artisan passport:install --no-interaction`
- 测试：`php artisan test`（= `vendor/bin/phpunit`）；单个测试：`php artisan test --filter=ExampleTest`
- 格式化：`vendor/bin/pint`（无 pint.json，用默认预设）
- 路由核对：`php artisan route:list --path=roles`

前端（`cd frontend`）：
- `npm run dev`（:5173，开发期 `/api` 由 Vite proxy 到 `127.0.0.1:8000`）
- `npm run build` → `dist/`（生产由 Nginx 托管并反代 `/api`）
- 首次 `npm install`

测试账号：`13800138000 / password`（admin，全部权限）；`13900139000 / password`（无权限，演示隔离）。

## 数据库

MySQL 8，库 `ai_test`，`127.0.0.1`，root/root。**本机没有 mysql CLI**——查库不要用 `mysql` 命令，改用：`php -r` 脚本 bootstrap Laravel 走 `DB` facade，或 `php artisan tinker`、`php artisan db:seed`。例：
```php
php -r 'require "vendor/autoload.php"; $app=require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); use Illuminate\Support\Facades\DB; foreach(DB::table("roles")->get() as $r) echo $r->id." ".$r->name."\n";'
```

## 架构

### 后端三层分层（核心约束）

Controller → Service → Repository。
- **Repository 是唯一允许直接操作 Eloquent / DB 的层**。Service 只做编排，Controller 只做 HTTP 编排 + 校验 + 调 Service。
- 所有 Repository 继承 `App\Repositories\BaseRepository`（提供 `query/findById/findOneBy/findManyBy/paginate/create/update/delete` + `applyWhere`），子类只需实现 `modelClass()` 返回 Model FQCN。
- 依赖注入用**具体类**，未用接口绑定（直接 inject Repository 类，非 Contract）。
- 新增业务模块的标准样板：`Http/Resources/XxxResource` + `Repositories/XxxRepository` + `Services/XxxService` + `Http/Controllers/Api/V1/XxxController` + `routes/api.php`。
- 赋权：给用户赋角色用 `$user->syncRoles()`、给角色赋权限用 `$role->syncPermissions()`（spatie API，自动清权限缓存）；唯一性查重放 Service 层（Repository 查 + 抛 `ValidationException`），不要在 Controller 用 `unique` 规则。

### 认证

- 登录是 **phone**（正则 `^1\d{10}$`）。`User::findForPassport` 按 phone 查、`validateForPassportPasswordGrant` 校验 bcrypt。
- `AuthController` 继承 Passport 的 `AccessTokenController`，内部组装 PSR-7 请求调 `issueToken`，**不把 client_secret 暴露给前端**。token 撤销等数据访问下沉到 `AccessTokenRepository`。
- guard 全程是 **`api`**：`User::$guard_name='api'`，且所有角色/权限的 `guard_name` 必须也是 `api`——三者不一致则权限校验失效。
- token / refresh_token 统一在「北京次日凌晨 4 点」过期（`AuthServiceProvider::boot`）。

### RBAC 与「无超管兜底」（重要）

- 后端中间件 `role_or_permission:xxx`（项目自定义的 `RoleOrPermissionMiddleware`，继承 spatie），**严格校验，没有任何 `Gate::before` 超管兜底**。
- admin 角色能访问一切，**仅仅因为** seeder 把全部 api 权限 sync 给了 admin 角色。前端 `user` store 另有 `isSuperAdmin` 兜底（roles 含 `admin` 即放行全部），但**后端没有等价兜底**——前端放行不代表后端放行。

### 统一响应信封

- 业务接口统一 `{ code, message, data }`（`JsonResponseTrait::success/error`，`code=0` 为成功）。
- 但 **登录 / 刷新返回原始 OAuth2 响应**（`{access_token, refresh_token, ...}`，非信封）。
- 前端 `api/request.js` 拦截器用「响应体是否同时含 `code` 和 `data`」区分二者：信封解包 `data`，原始原样返回。

### 前端权限控制

- 路由 `meta.permission` + `store.user.hasPermission()` 控制菜单（`Sidebar.vue` 按权限过滤 children）。
- 按钮 `v-permission="'xxx'"`（`main.js` 全局注册，无权则从 DOM 移除节点）。
- 刷新水合：`router/index.js` 的 `beforeEach` 在「有 token 但无 userInfo」时先拉 `/api/auth/me`。
- 401 处理：非登录接口 401 → 拦截器清 token 跳 `/login`；登录接口 400/401 → 提示「账号或密码错误」（见坑 #4）。

## 容易踩的坑（务必注意）

1. **新增功能模块时，权限必须三处同步**，否则连 admin 也进不去新页面（后端无兜底）：
   - `config/rbac.php` 的 `permissions` 数组加新权限 → 重跑 `php artisan db:seed`（默认入口 `DatabaseSeeder` 委托 `PermissionSeeder`，幂等：`firstOrCreate` 权限 + admin `syncPermissions` 全部 api 权限 + `updateOrCreate` 超管账号，重跑安全、不破坏其他数据）。
   - 路由挂 `role_or_permission:xxx`（读/写可分组）。
   - 前端路由 `meta.permission` + 按钮 `v-permission`。
2. **Passport 12 两个要点**：
   - 默认关闭 password grant，必须在 `AuthServiceProvider::boot` 调 `Passport::enablePasswordGrant()`，否则登录报 `unsupported_grant_type`。
   - 新克隆 / 重置后端必须 `php artisan passport:install`（生成密钥 **且** 创建 password client）。光跑 `passport:keys` 只生成密钥不建 client，登录时 `AccessTokenRepository::getPasswordClient()` 会抛 `RuntimeException`。顺序：先 `migrate`（才有 `oauth_clients` 表）再 `passport:install`。
3. **纯 API 后端的 401**：已覆写 `app/Exceptions/Handler::unauthenticated()` 直接返回 JSON 401。若改回默认行为，未登录请求会因尝试 session 重定向而 500。
4. **登录凭证错误是 HTTP 400（OAuth `invalid_grant`）而不是 401**——前端拦截器对 `/auth/login` 的 400/401 统一提示「账号或密码错误」，不要只按 401 处理。
5. **本机无 mysql CLI**，查库走 PHP/PDO（见「数据库」）。
6. **`admin` 角色受保护**：`RoleService` 禁止改名 / 删除该角色；前端 `isSuperAdmin` 据此兜底。新增角色管理类逻辑时保持此保护。
7. spatie 有权限缓存：直接改 `role_has_permissions` 表不会即时生效，应走 spatie API（`syncPermissions` / `assignRole` 等会自动清缓存）。

## 关键文件速查

后端：
- `routes/api.php` —— 路由 + 权限中间件分组
- `app/Providers/AuthServiceProvider.php` —— `enablePasswordGrant` + token 过期
- `app/Exceptions/Handler.php` —— 401 JSON 覆写
- `config/rbac.php` —— 系统权限项定义（加新权限的唯一入口）
- `database/seeders/PermissionSeeder.php` —— 权限 / admin 角色 / 超管账号种子（`DatabaseSeeder` 为默认入口调用它）
- `config/auth.php` —— `api` guard = passport
- `app/Repositories/BaseRepository.php` —— Repository 基类，新模块照此继承
- `app/Services/{UserService,RoleService}.php` —— 业务编排代表；赋权见 `syncRoles` / `syncPermissions`

前端：
- `src/api/request.js` —— 信封解包 / 401·403·422 拦截
- `src/stores/user.js` —— token / roles / permissions / `hasPermission` / `isSuperAdmin`
- `src/router/index.js` —— 路由 + beforeEach 守卫 + 菜单结构（`/system` 系统设置下挂用户/角色管理）
- `src/layout/components/Sidebar.vue` —— 按权限过滤菜单
- `src/main.js` —— `v-permission` 指令 + Element Plus 图标全局注册
