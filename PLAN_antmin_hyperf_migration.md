# Antmin Laravel → Hyperf 3.1 迁移方案

## 背景
- 源目录：`/Users/apple/PhpstormProjects/local179/antdev/antmin`
- 目标目录：`/Users/apple/PhpstormProjects/local179/antmin_hyperf`
- 目标：将 Laravel 包完整迁移为 Hyperf 3.1 依赖包，并尽量保持接口路径、action 分发方式、返回结构兼容。
- 范围说明：`请求日志` 模块不在本次迁移范围内，不迁移对应路由、配置、数据表和运行时实现。

## 1. 先建立 Hyperf 包骨架
优先完成：
- `composer.json`
- `src/ConfigProvider.php`
- `publish/antmin.php`
- `publish/migrations/*`
- `src/Route/RouteRegistrar.php`
- `src/Exception/CommonException.php`
- `src/Common/Base.php`
- `src/Common/Limit.php`

`composer.json` 以 Hyperf 3.1 组件为核心，至少覆盖：HTTP Server、DI、Config、Validation、DbConnection/Model、Redis、Cache、Filesystem、Mailer、Guzzle。JWT 改为包内抽象，不继续绑定 Laravel 生态实现。

## 2. 先迁基础兼容层，再迁业务模块
优先迁移底座：
- 返回结构复用 `Base::sucJson()` / `Base::errJson()` 语义，保持响应 envelope 不变。
- 异常输出复用 `CommonException` 语义。
- 路由前缀和接口名保持兼容。
- 鉴权中间件保留 `Access-Token` 读取和 `accountId` 注入行为。

先保住 `operate() + action` 分发模式，不急于改造成 REST 风格。
关键入口：
- `src/Http/Controllers/EnterController.php`
- `src/Http/Controllers/UploadController.php`
- `src/Http/Controllers/AccountController.php`

## 3. 把 Laravel 依赖替换成 Hyperf 可注入服务
重点替换：
- `Illuminate\Http\Request` / `request()` / `response()` → Hyperf Request/Response
- `Validator` / `$request->validate()` → Hyperf Validation
- `DB` / Eloquent Model → Hyperf DbConnection + Model
- `Redis` / `Cache` → Hyperf Redis / Cache
- `Storage` / 上传文件 → Hyperf Filesystem + UploadedFile
- `Mail` → Hyperf Mailer
- `Hash` → `PasswordHasherInterface`
- `JWTAuth` → `TokenServiceInterface`

建议契约：
- `src/Contract/TokenServiceInterface.php`
- `src/Contract/FileStorageInterface.php`
- `src/Contract/PasswordHasherInterface.php`

## 4. 按“认证与权限优先”顺序迁移业务
1. 认证与账号
   - `src/Models/Account.php`
   - `src/Http/Repositories/TokenRepository.php`
   - `src/Http/Repositories/AccountRepository.php`
   - `src/Http/Services/LoginService.php`
   - `src/Http/Services/AccountService.php`
2. 角色 / 权限 / 菜单
   - `src/Models/Role.php`
   - `src/Models/Permission.php`
   - `src/Models/Menu.php`
   - 相关 Repository / Service / Controller
3. 上传 / 邮件验证码 / 安全限制
   - `src/Http/Services/EmailService.php`
   - `src/Http/Services/SafeService.php`
   - `src/Common/Limit.php`
   - `src/Http/Controllers/UploadController.php`
4. 操作日志 / 版本管理 / 系统设置
   - `src/Http/Services/VersionService.php`
   - 相关 Repository / Service / Controller

## 5. 数据层不要照搬旧 migration，要按真实模型重建
现有 Laravel migration 与当前模型/仓储不一致，不能直接迁。
应以 `src/Models/*` 与 `src/Http/Repositories/*` 的真实字段/关系为准，重建 Hyperf migration。
核心表优先核对：
- `system_account`
- `system_role`
- `system_permission`
- `system_menu`
- `system_account_role`
- `system_role_permission`
- `system_menu_permission`
- `system_operate_log`
- `system_set*`

## 6. 迁移过程中顺手修正阻塞 Hyperf 的问题
- `src/Mail/CodeMail.php` 命名空间与类型引用问题
- 多处 `uuid()` / `random()` / `str_random()` 依赖宿主全局函数
- 多处直接使用 `request()`、`now()`、`env()`、`config()`

## 7. 验证顺序
1. `composer dump-autoload` 与依赖安装成功
2. 最小 Hyperf 示例应用能加载该包与 `ConfigProvider`
3. 路由注册后以下路径保持可调用：
   - `/api/adminconsole/systemLogin`
   - `/api/adminconsole/systemRegister`
   - `/api/adminconsole/systemIndexOperate`
   - `/api/adminconsole/systemUploadOperate`
4. 登录后校验：Token 签发、解析、过期、Redis 多终端限制
5. 账号/角色/权限/菜单增删改查与原返回结构一致
6. 上传接口能落盘并返回兼容 URL 结构
7. 邮件验证码与限流链路分别跑通
8. 对关键模块补最少集成测试：登录鉴权、菜单导航、账号 CRUD、上传
