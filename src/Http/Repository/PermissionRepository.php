<?php

declare(strict_types=1);

namespace Antmin\Http\Repository;

use Antmin\Common\Base;
use Antmin\Model\Permission;
use Antmin\Model\Role;
use Antmin\Model\RolePermission;

class PermissionRepository
{
    public function __construct(
        private readonly Permission $permissionModel,
        private readonly RolePermission $rolePermissionModel,
        private readonly Role $roleModel,
        private readonly Base $base,
    ) {
    }

    public function getAllPermissionsByAccountId(int $accountId): array
    {
        $ids = $this->getAllPermissionsIdsByAccountId($accountId);
        return $this->permissionModel->newQuery()->whereIn('id', $ids)->get()->toArray();
    }

    public function getAllPermissionsIdsByAccountId(int $accountId): array
    {
        if ($accountId === 1) {
            return $this->getAllPermissionsIds();
        }

        $roleIds = $this->roleModel->getRolesIdsByAccountId($accountId);
        return $this->getAllPermissionsIdsByRoleIds($roleIds);
    }

    public function getParentPermissionsByAccountId(int $accountId): array
    {
        $ids = $this->getParentPermissionsIdsByAccountId($accountId);
        return $this->permissionModel->newQuery()->whereIn('id', $ids)->get()->toArray();
    }

    public function getParentPermissionsIdsByAccountId(int $accountId): array
    {
        if ($accountId === 1) {
            return $this->getParentPermissionsIds();
        }

        $roleIds = $this->roleModel->getRolesIdsByAccountId($accountId);
        return $this->getParentPermissionsIdsByRoleIds($roleIds);
    }

    public function getAllPermissionsIdsByRoleIds(array $roleIds): array
    {
        if (in_array($this->roleModel->getSupperRoleId(), $roleIds, true)) {
            return $this->getAllPermissionsIds();
        }

        return $this->rolePermissionModel->newQuery()->whereIn('role_id', $roleIds)->pluck('permission_id')->toArray();
    }

    public function getParentPermissionsIdsByRoleIds(array $roleIds): array
    {
        if (in_array($this->roleModel->getSupperRoleId(), $roleIds, true)) {
            return $this->getParentPermissionsIds();
        }

        return $this->rolePermissionModel->newQuery()->whereIn('role_id', $roleIds)->pluck('permission_id')->toArray();
    }

    public function getTree(array $allPermission, int $id, int $isShowCheck = 0): ?array
    {
        $temp = [];
        foreach ($allPermission as $k => $v) {
            if ((int) ($v['pid'] ?? 0) === $id) {
                $temp[$k]['action'] = $v['vid'];
                $temp[$k]['title'] = $v['title'];
                if ($isShowCheck === 1) {
                    $temp[$k]['defaultCheck'] = false;
                }
            }
        }

        return $temp === [] ? null : array_values($temp);
    }

    public function getParentFormatToAccountList(int $limit): array
    {
        return $this->getParentList($limit);
    }

    public function getParentFormatToRoleList(int $limit): array
    {
        return $this->getParentList($limit, ['status' => 1]);
    }

    public function getParentFormatList(int $limit): array
    {
        return $this->getParentList($limit);
    }

    public function getParentFormatTree(int $limit = 99): array
    {
        return $this->getParentList($limit);
    }

    public function getParentList(int $limit, array $search = []): array
    {
        $query = $this->permissionModel->newQuery()->where('pid', 0)->orderByDesc('id');
        if (! empty($search['status'])) {
            $query->where('status', $search['status']);
        }
        return $this->base->listFormat($limit, $query);
    }

    public function getInfoByVidAndPid(string $vid, int $pid): array
    {
        $one = $this->permissionModel->newQuery()->where('vid', $vid)->where('pid', $pid)->first();
        return $one ? $one->toArray() : [];
    }

    public function add(array $info): int
    {
        $one = $this->permissionModel->newQuery()->where('vid', $info['vid'])->where('pid', $info['pid'])->first();
        if (! $one) {
            $one = $this->permissionModel->create($info);
            if ((int) $info['pid'] === 0) {
                $this->autoAddChild((int) $one->id);
            }
        }

        return (int) $one->id;
    }

    public function edit(array $info, int $id): bool
    {
        return (bool) $this->permissionModel->newQuery()->where('id', $id)->update($info);
    }

    public function editByPid(array $info, int $pid): bool
    {
        return (bool) $this->permissionModel->newQuery()->where('pid', $pid)->update($info);
    }

    public function del(int $id): bool
    {
        $this->permissionModel->newQuery()->where('id', $id)->delete();
        $this->permissionModel->newQuery()->where('pid', $id)->delete();
        return true;
    }

    public function getInfo(int $id): array
    {
        $one = $this->permissionModel->find($id);
        return $one ? $one->toArray() : [];
    }

    private function getAllPermissionsIds(): array
    {
        return $this->permissionModel->newQuery()->pluck('id')->toArray();
    }

    private function getParentPermissionsIds(): array
    {
        return $this->permissionModel->newQuery()->where('pid', 0)->pluck('id')->toArray();
    }

    private function autoAddChild(int $id): void
    {
        foreach ([
            ['view', '查看'],
            ['add', '添加'],
            ['update', '更新'],
            ['delete', '删除'],
        ] as [$vid, $title]) {
            $this->permissionModel->create([
                'vid' => $vid,
                'action_rule' => $vid,
                'title' => $title,
                'pid' => $id,
            ]);
        }
    }
}
