<?php

declare(strict_types=1);

namespace Antmin\Http\Service;

use Antmin\Exceptions\CommonException;
use Antmin\Http\Repository\AccountRepository;
use Antmin\Http\Repository\AccountRoleRepository;
use Antmin\Http\Repository\PermissionRepository;
use Antmin\Http\Repository\RolePermissionsRepository;
use Antmin\Http\Repository\RoleRepository;

class RoleService
{
    public function __construct(
        private readonly RoleRepository $roleRepo,
        private readonly AccountRepository $accountRepo,
        private readonly PermissionRepository $permissionRepo,
        private readonly RolePermissionsRepository $rolePermissionsRepo,
        private readonly AccountRoleRepository $accountRoleRepo,
    ) {
    }

    public function index(int $limit, int $accountId): array
    {
        $this->checkPermissions($accountId);
        return [
            'roles' => $this->roleRepo->getFormatList($limit),
            'rules' => $this->permissionRepo->getParentFormatToRoleList(99),
        ];
    }

    public function add(string $vid, string $name, int $accountId): int
    {
        $this->checkPermissions($accountId);
        if ($this->roleRepo->getInfoByVid($vid) !== []) {
            throw new CommonException('角色标识已存在');
        }
        if ($this->roleRepo->getInfoByName($name) !== []) {
            throw new CommonException('角色名已存在');
        }

        return $this->roleRepo->add([
            'vid' => $vid,
            'name' => $name,
        ]);
    }

    public function edit(array $info, int $id, int $accountId): bool
    {
        $this->checkPermissions($accountId);
        $this->checkSupperRoleId($id);

        $name = (string) ($info['name'] ?? '');
        $one = $this->roleRepo->getInfoByName($name);
        if ($one !== [] && $id !== (int) $one['id']) {
            throw new CommonException('角色名已存在');
        }
        if ($name !== '') {
            $this->roleRepo->edit(['name' => $name], $id);
        }
        return true;
    }

    public function roleRuleEdit(array $rules, int $id, int $accountId): bool
    {
        $this->checkPermissions($accountId);
        $this->checkSupperRoleId($id);
        $this->rolePermissionsRepo->deleteByRoleId($id);
        foreach ($rules as $permissionId) {
            $this->rolePermissionsRepo->add($id, (int) $permissionId);
        }
        return true;
    }

    public function del(int $id, int $accountId): bool
    {
        $this->checkPermissions($accountId);
        $this->checkSupperRoleId($id);
        if ($this->accountRoleRepo->isHasAccountByRoleId($id)) {
            throw new CommonException('该角色存在账号中，请先处理');
        }
        $this->rolePermissionsRepo->deleteByRoleId($id);
        return $this->roleRepo->del($id);
    }

    public function editStatus(int $id, int $accountId): bool
    {
        $this->checkPermissions($accountId);
        $this->checkSupperRoleId($id);
        $info = $this->roleRepo->getInfo($id);
        $status = empty($info['status']) ? 1 : 0;
        return $this->roleRepo->edit(['status' => $status], $id);
    }

    private function checkPermissions(int $accountId): void
    {
        if (! $this->accountRepo->isSuperAdmin($accountId)) {
            throw new CommonException('非超级管理员无权操作');
        }
    }

    private function checkSupperRoleId(int $id): void
    {
        if ($id === $this->roleRepo->getSupperRoleId()) {
            throw new CommonException('超级管理员角色不可删除');
        }
    }
}
