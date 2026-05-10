<?php

declare(strict_types=1);

namespace Antmin\Http\Service;

use Antmin\Exceptions\CommonException;
use Antmin\Http\Repository\AccountRepository;
use Antmin\Http\Repository\PermissionRepository;

class PermissionsService
{
    public function __construct(
        private readonly AccountRepository $accountRepo,
        private readonly PermissionRepository $permissionRepo,
    ) {
    }

    public function ruleList(int $limit, int $opId): array
    {
        $this->checkPermissions($opId);
        return $this->permissionRepo->getParentFormatList($limit);
    }

    public function ruleListTree(): array
    {
        return $this->permissionRepo->getParentFormatTree();
    }

    public function ruleAdd(array $info, int $opId): int
    {
        $this->checkPermissions($opId);
        $one = $this->permissionRepo->getInfoByVidAndPid($info['vid'], $info['pid']);
        if ($one !== []) {
            throw new CommonException('同级别的识别码不可相同');
        }
        return $this->permissionRepo->add($info);
    }

    public function ruleEdit(array $info, int $id, int $opId): bool
    {
        $this->checkPermissions($opId);
        if ($this->permissionRepo->getInfo($id) === []) {
            throw new CommonException('信息不存在');
        }
        $one = $this->permissionRepo->getInfoByVidAndPid($info['vid'], $info['pid']);
        if ($one !== [] && (int) $one['id'] !== $id) {
            throw new CommonException('同级别的识别码不可相同');
        }
        return $this->permissionRepo->edit($info, $id);
    }

    public function ruleEditStatus(int $id, int $opId): bool
    {
        $this->checkPermissions($opId);
        $one = $this->permissionRepo->getInfo($id);
        if ($one === []) {
            throw new CommonException('信息不存在');
        }
        $status = empty($one['status']) ? 1 : 0;
        $this->permissionRepo->edit(['status' => $status], $id);
        $this->permissionRepo->editByPid(['status' => $status], $id);
        return true;
    }

    public function ruleDel(int $id, int $opId): bool
    {
        $this->checkPermissions($opId);
        return $this->permissionRepo->del($id);
    }

    public function handleGetPermissionByAccountId(int $accountId): array
    {
        $all = $this->permissionRepo->getAllPermissionsByAccountId($accountId);
        $parent = $this->permissionRepo->getParentPermissionsByAccountId($accountId);

        $permission[0] = [
            'id' => null,
            'action' => null,
            'actionEntitySet' => null,
            'actionList' => null,
            'actions' => null,
            'dataAccess' => null,
            'permissionId' => null,
            'title' => null,
        ];

        if ($parent === []) {
            return ['permissions' => $permission];
        }

        foreach ($parent as $k => $v) {
            $permission[$k] = [
                'id' => $v['id'],
                'action' => $v['vid'],
                'actionEntitySet' => $this->permissionRepo->getTree($all, (int) $v['id'], 1),
                'actionList' => null,
                'actions' => $this->permissionRepo->getTree($all, (int) $v['id']),
                'dataAccess' => null,
                'permissionId' => $v['vid'],
                'title' => $v['title'],
            ];
        }

        return ['permissions' => $permission];
    }

    private function checkPermissions(int $accountId): void
    {
        if (! $this->accountRepo->isSuperAdmin($accountId)) {
            throw new CommonException('非超级管理员无权操作');
        }
    }
}
