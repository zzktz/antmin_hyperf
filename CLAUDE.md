# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

This repository is a Hyperf 3.1 library package that provides an Antmin admin backend module. It is not a standalone app: it plugs into a host Hyperf application through `Antmin\ConfigProvider` and publishes its config to `config/autoload/antmin.php`.

## Common commands

## Install dependencies

```bash
composer install
```

## Run tests

```bash
composer test
# or
vendor/bin/phpunit --colors=always
```

## Run a single test file

```bash
vendor/bin/phpunit tests/Unit/Common/BaseTest.php
vendor/bin/phpunit tests/Unit/Exception/CommonExceptionHandlerTest.php
```

## Run a single test method

```bash
vendor/bin/phpunit --filter testSucJsonBuildsSuccessEnvelope
```

## Refresh autoload files

```bash
composer dump-autoload
```

## Package integration notes

- Hyperf loads this package via Composer `extra.hyperf.config` pointing to `Antmin\ConfigProvider`.
- Publishing the package config installs `publish/antmin.php` to the host app at `config/autoload/antmin.php`.
- `composer dump-autoload` triggers `post-autoload-dump`, which removes `runtime/container` so Hyperf DI metadata is rebuilt.

## Architecture

## Entry points and bootstrapping

- `src/ConfigProvider.php` is the package entry point. It wires DI bindings, registers the exception handler, registers the boot listener that adds routes, and declares the publishable config.
- `src/Listener/RegisterRoutesListener.php` listens for `Hyperf\Framework\Event\BootApplication` and calls `Antmin\Route\RouteRegistrar::register()`.
- `src/Route/RouteRegistrar.php` registers a single route group under the configurable prefix from `antmin.route_prefix` (default `api/adminconsole`).

## HTTP surface

The package exposes only a few concrete HTTP endpoints, then multiplexes most admin actions through `action` parameters:

- `/systemLogin`, `/systemRegister`, `/sendCodeByEmail`, `/systemResetPassword`
- `/systemIndexOperate` → `EnterController::operate()` dispatches to account/menu/role/permission operations based on an `action` request field.
- `/systemUploadOperate` and `/systemUploadEditor` handle uploads and UEditor integration.

When changing API behavior, check both the route registrar and the `ACTIONS` allowlists in controllers such as `EnterController` and `UploadController`.

## Request flow

- `src/Http/Middleware/Middleware.php` wraps the whole route group.
- Requests listed in `src/Http/Middleware/Filter.php` are anonymous; everything else requires an `Access-Token` header.
- The middleware also applies per-IP rate limiting through `Antmin\Common\Limit`, then resolves the current user ID from the token and stores `accountId` plus `request_start_time` on the request attributes.
- Response envelopes are centralized in `Antmin\Common\Base`: successful responses go through `sucJson()`, failures through `errJson()`, both including `useTime`.
- `src/Exception/Handler/CommonExceptionHandler.php` converts `CommonException` into that standard JSON error envelope and preserves the exception HTTP status code.

## Controller / service / repository layering

The package uses a fairly strict three-layer flow:

- Controllers validate and normalize request input.
- Services implement business rules and permission checks.
- Repositories talk to models and assemble the response-oriented data structures expected by the frontend.

Typical examples:

- `AccountController` → `AccountService` / `LoginService` → `AccountRepository`, `TokenRepository`, `SmsRepository`
- `MenuController` → `MenuService` → `MenuRepository`, `MenuPermissionRepository`
- `PermissionsController` → `PermissionsService` → `PermissionRepository`
- `RoleController` → `RoleService` → role and permission repositories

If a change affects authorization or returned payload shape, inspect the whole controller → service → repository chain, not just one layer.

## Operate-style controllers

Two controllers act as action dispatchers rather than conventional REST controllers:

- `src/Http/Controller/EnterController.php` accepts an `action` field and forwards to protected methods listed in its `ACTIONS` constant.
- `src/Http/Controller/UploadController.php` does the same for upload sub-actions.
- Dispatch safety is enforced in `AbstractController::resolveOperateAction()`, which checks the allowlist, verifies the method exists, and ensures the method is declared on the concrete controller.

When adding a new admin action, you usually need to:

1. add the action name to the relevant `ACTIONS` constant,
2. implement the protected controller method,
3. connect it to the appropriate service method.

## Authentication and token model

- Token abstraction lives behind `Antmin\Contract\TokenServiceInterface`.
- `src/Token/JwtTokenService.php` is the default implementation bound in `ConfigProvider`.
- JWTs are signed with the configured secret and validated against issuer, audience, time validity, and a configurable claim name/value (`antmin.token.role_claim`).
- Tokens are also stored in Redis sorted sets per account. `max_tokens_per_user` limits concurrent active tokens by trimming older entries, so token validity depends on both JWT validity and Redis membership.
- `src/Http/Repository/TokenRepository.php` is only a thin wrapper around the token service.

If token behavior changes, review both JWT claims and Redis token retention logic.

## Authorization model

Authorization is role/permission driven, but many write operations are effectively super-admin only:

- `AccountRepository::isSuperAdmin()` hard-codes super admin to account ID `1`.
- `AccountService`, `MenuService`, and `PermissionsService` gate mutating operations with that check.
- Menu visibility is derived from role-permission-menu joins, but the super admin bypasses those filters.

Be careful when changing authorization: several services depend on the implicit “account ID 1 is root” rule.

## Data and response shaping

- Models under `src/Model` map to the package’s admin tables.
- Repositories often return frontend-specific payloads instead of raw model arrays.
- `MenuService::getMenuNav()` builds the navigation tree shape expected by the admin UI (`name`, `component`, `path`, `meta`, etc.).
- `PermissionsService::handleGetPermissionByAccountId()` builds the permission structure returned by `getUserInfo`.
- `AccountRepository::getFormatList()` returns paginated account data plus role/rule decorations.

When debugging payload mismatches, inspect repositories and service formatting before changing controllers.

## Upload handling

- `UploadController` supports image/file/video uploads plus a UEditor-compatible endpoint.
- Storage is abstracted by `Antmin\Contract\FileStorageInterface`; the default `src/Support/FileStorage.php` stores files under `runtime/antmin` using Flysystem local storage.
- Public URLs are derived from `antmin.upload.url`; relative paths are returned unchanged if no base URL is configured.
- Avatar uploads update the account record when the request `type` equals `avatar`.

## Common utilities

- `src/Common/Base.php` is a central helper for JSON envelopes, validation helpers, pagination formatting, URL prefixing, and some small utility methods.
- `src/Common/Limit.php` provides Redis-backed request throttling.
- `src/Support/HyperfContext.php` is the helper used to access request/response/config from the Hyperf container context.
- Password hashing and verification are abstracted via `PasswordHasherInterface` and bound to `Support\PasswordHasher`.

## Tests

- Tests currently cover utility/handler behavior only (`tests/Unit/Common/BaseTest.php` and `tests/Unit/Exception/CommonExceptionHandlerTest.php`).
- PHPUnit is configured by `phpunit.xml.dist` and bootstraps through `vendor/autoload.php`.
- There is no broader integration test harness in this package yet, so changes in controllers/services/repositories may require careful manual reasoning or new tests.
