<?php

declare(strict_types=1);

namespace Antmin\Http\Service;

use Antmin\Common\Base;
use Antmin\Contract\PasswordHasherInterface;
use Antmin\Exceptions\CommonException;
use Antmin\Http\Repository\AccountRepository;
use Antmin\Http\Repository\AccountRoleRepository;
use Antmin\Http\Repository\PermissionRepository;
use Antmin\Http\Repository\RoleRepository;
use Antmin\Http\Repository\TokenRepository;

class AccountService
{
    private const DEF_PASSWORD = 'a@123456';

    public function __construct(
        private readonly AccountRepository $accountRepo,
        private readonly RoleRepository $roleRepo,
        private readonly PermissionRepository $permissionRepo,
        private readonly TokenRepository $tokenRepo,
        private readonly AccountRoleRepository $accountRoleRepo,
        private readonly PasswordService $passwordService,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly Base $base,
    ) {
    }

    public function getAccountIdByToken(string $token): int
    {
        try {
            $accountId = $this->tokenRepo->getIdByToken($token);
            $this->ensureAccountIsActive($accountId);
            return $accountId;
        } catch (CommonException $exception) {
            throw new CommonException($exception->getMessage(), [], -1, 401);
        } catch (\Throwable $throwable) {
            throw new CommonException($throwable->getMessage(), [], -1, 401);
        }
    }

    public function getAccountBaseInfo(int $accountId): array
    {
        $account = $this->accountRepo->getInfo($accountId);
        if ($account === []) {
            throw new CommonException('用户信息不存在');
        }

        return [
            'id' => $account['id'],
            'name' => $account['name'],
            'username' => $account['nickname'],
            'roleName' => $this->roleRepo->getRoleNameByAccountId($accountId),
            'mobile' => $account['mobile'],
            'email' => $account['email'],
            'birthday' => $account['birthday'],
            'avatar' => !empty($account['avatar']) ? $account['avatar'] : '',
        ];
    }

    public function accountList(int $limit, int $accountId): array
    {
        $this->checkPermissions($accountId);
        return [
            'users' => $this->accountRepo->getFormatList($limit),
            'roles' => $this->roleRepo->getFormatAccountList(99),
            'rules' => $this->permissionRepo->getParentFormatToAccountList(99),
        ];
    }

    public function accountAdd(array $info, int $accountId): int
    {
        $this->checkPermissions($accountId);
        $this->passwordService->checkPasswordStrength((string) $info['password']);

        if (empty($info['roles'])) {
            throw new CommonException('角色值不存在');
        }
        if (in_array(1, $info['roles'], true)) {
            throw new CommonException('超级管理员角色不可以添加');
        }

        $name = $this->base->random(8, 'abcdefghijkmnpqrstuvwxyz');
        if ($this->accountRepo->getInfoByName($name) !== []) {
            throw new CommonException('账号名已存在');
        }
        if ($this->accountRepo->getInfoByMobile((string) $info['mobile']) !== []) {
            throw new CommonException('手机号已存在');
        }
        if ($this->accountRepo->getInfoByEmail((string) $info['email']) !== []) {
            throw new CommonException('邮箱已存在');
        }

        $info['name'] = $name;
        $info['password'] = $this->passwordHasher->hash((string) $info['password']);
        return $this->accountRepo->add($info);
    }

    public function accountEdit(array $info, int $id, int $accountId): bool
    {
        $this->checkPermissions($accountId);

        $byMobile = $this->accountRepo->getInfoByMobile((string) $info['mobile']);
        if ($byMobile !== [] && $id !== (int) $byMobile['id']) {
            throw new CommonException('手机号已存在');
        }
        $byEmail = $this->accountRepo->getInfoByEmail((string) $info['email']);
        if ($byEmail !== [] && $id !== (int) $byEmail['id']) {
            throw new CommonException('邮箱已存在');
        }
        if (in_array(1, $info['roles'] ?? [], true)) {
            throw new CommonException('超级管理员角色不可以添加');
        }

        $roles = $info['roles'] ?? [];
        unset($info['roles']);

        $result = $this->accountRepo->edit($info, $id);
        if ($roles !== []) {
            $this->accountRepo->editRole(['roles' => $roles], $id);
        }

        return $result;
    }

    public function personalEdit(string $field, string $value, int $accountId): bool
    {
        $allowedFields = ['mobile', 'email', 'nickname'];
        if (! in_array($field, $allowedFields, true)) {
            throw new CommonException('不允许修改该字段');
        }
        if ($value === '') {
            throw new CommonException('字段值不能为空');
        }

        $existing = $this->accountRepo->findByField($field, $value);
        if ($existing !== [] && ((int) ($existing['id'] ?? 0)) !== $accountId) {
            throw new CommonException(match ($field) {
                'mobile' => '手机号已存在',
                'email' => '邮箱已存在',
                default => '昵称已存在',
            });
        }

        return $this->accountRepo->edit([$field => $value], $accountId);
    }

    public function editStatus(int $id, int $accountId): bool
    {
        $this->checkPermissions($accountId);
        $account = $this->accountRepo->getInfo($id);
        if ($account === []) {
            throw new CommonException('用户不存在');
        }
        $newStatus = empty($account['status']) ? 1 : 0;
        return $this->accountRepo->editStatus($newStatus, $id);
    }

    public function accountDel(int $id, int $accountId): void
    {
        $this->checkPermissions($accountId);
        if ($this->accountRepo->isSuperAdmin($id)) {
            throw new CommonException('超级管理员不可以删除');
        }
        $this->accountRepo->del($id);
    }

    public function reInitPassword(int $id, int $accountId): bool
    {
        $this->checkPermissions($accountId);
        if ($this->accountRepo->getInfo($id) === []) {
            throw new CommonException('用户不存在');
        }

        return $this->accountRepo->updatePassword($this->passwordHasher->hash(self::DEF_PASSWORD), $id);
    }

    public function logout(int $accountId, string $token): void
    {
        $this->tokenRepo->revokeToken($accountId, $token);
    }

    private function checkPermissions(int $accountId): void
    {
        if (! $this->accountRepo->isSuperAdmin($accountId)) {
            throw new CommonException('非超级管理员无权操作');
        }
    }

    private function ensureAccountIsActive(int $accountId): void
    {
        $account = $this->accountRepo->getInfo($accountId);
        if ($account === []) {
            throw new CommonException('用户不存在');
        }
        if ((int) ($account['status'] ?? 0) !== 1) {
            throw new CommonException('账号已被禁用，请联系管理员');
        }
    }
}
