# Antmin Hyperf 迁移进度总结

## 背景
- 项目：`antmin_hyperf`
- 目标：将原 Laravel 风格实现迁移为 Hyperf 3.1 包，尽量保持接口路径、`action` 分发方式、返回结构兼容。
- 范围说明：`请求日志` 模块明确不在本次迁移范围内，相关实现已按约定排除。

## 当前已完成的关键迁移工作

### 1. 路由与配置
- 已完成路由注册迁移，并由监听器在应用启动时注册路由。
- 路由前缀已改为真正从配置读取，而不是写死在源码里：
  - `src/Listener/RegisterRoutesListener.php`
  - `src/Route/RouteRegistrar.php`
- 发布配置文件已保留 Hyperf 包所需默认配置：
  - `publish/antmin.php`

### 2. Laravel helper / 运行时依赖清理
- 已清理 `src` 运行时代码中的 Laravel 风格 `env()` 调用。
- 短信验证码相关配置已改为通过 Hyperf 配置读取：
  - `src/Http/Repository/SmsRepository.php`
- `request()` / `response()` 搜索结果中，剩余命中均为自定义上下文封装方法名，不再是 Laravel helper 残留。
- 发布配置中的 `env()` 也已移除，避免发布后配置文件仍依赖 Laravel 环境：
  - `publish/antmin.php`

### 3. 请求上下文与响应获取
- 已修正 `HyperfContext` 的 `response()` 获取逻辑，优先复用已有上下文，避免上下文存在时又回退到容器重新获取：
  - `src/Support/HyperfContext.php`

### 4. 中间件与鉴权链路
- 已修复中间件中对固定 `api/.../...` 路径层级的硬编码假设：
  - `src/Http/Middleware/Middleware.php`
- 现在中间件按路径最后一段识别接口方法名，能兼容自定义 `route_prefix`，不会因前缀变化导致免鉴权接口判断异常。

### 5. 请求日志范围清理
- 请求日志模块不在本次迁移范围内，这一点已持续贯彻。
- 当前检查结果显示，请求日志相关引用仍保持清理状态，没有重新混入本次迁移代码。

## 已完成验证
- `composer dump-autoload` 通过
- 全量 `php -l` 语法检查通过（此前阶段验证）
- 本轮新增修改的文件语法检查通过：
  - `src/Http/Middleware/Middleware.php`
  - `publish/antmin.php`
- 全仓搜索已无 `env()` 残留
- `src` 下未发现残留的 Laravel facade / `Illuminate` 依赖引用
- 中间件路径硬编码搜索已清理干净，不再依赖 `$parts[2]` 或 `str_starts_with($path, 'api')`

## 当前代码状态判断
整体来看，项目已经从“核心迁移未完成”进入“主体可用、继续收尾优化”的阶段：
- 核心路由注册、鉴权入口、上下文获取、配置读取方式已基本切到 Hyperf 语义
- 主要 Laravel helper 残留已清理
- 当前剩余问题更多偏向一致性优化、依赖注入风格统一、局部实现复用与收口

## 下一步优化方向
以下内容更偏“继续优化但不一定阻塞”：

1. **统一依赖注入风格**
   - 当前大部分代码已使用构造函数注入，但仍有个别类保留属性注入写法，建议统一。

2. **收紧动态 action 分发**
   - `EnterController` / `UploadController` 仍基于字符串动态调用方法，建议限制为仅允许当前控制器声明的 action 方法，避免误调父类辅助方法。

3. **减少重复的上传落盘实现**
   - 当前上传逻辑与 `FileStorage` 抽象之间还没有完全统一，存在直接拼接 `BASE_PATH`、`mkdir()`、`moveTo()` 的实现，可继续整理。

4. **清理未使用依赖和无效注入**
   - 存在个别无实际用途的注入项，可进一步移除以降低迁移噪音。

## 结论
截至目前，这个 Hyperf 迁移包的高优先级阻塞项已经明显减少，当前最适合的工作方式是继续做“定点收尾优化”而不是大范围重构，优先把动态分发安全性、DI 一致性、上传存储收口这几类问题逐个处理。
