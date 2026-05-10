<?php

declare(strict_types=1);

namespace Antmin\Http\Repository;

use Antmin\Exceptions\CommonException;
use Antmin\Model\RolePermission;
use Hyperf\DbConnection\Db;

class RolePermissionsRepository
{
    public function __construct(
        private readonly RolePermission $rolePermissionModel,
        private readonly PermissionRepository $permissionRepo,
    ) {
    }

    public function getPermissionsIdsByRoleIds(array $roleIds): array
    {
        $data = $this->rolePermissionModel->newQuery()
            ->whereIn('role_id', $roleIds)
            ->pluck('permission_id')
            ->toArray();

        return array_values(array_unique($data));
    }

    public function add(int $roleId, int $permissionId): int
    {
        return Db::transaction(function () use ($roleId, $permissionId) {
            $permission = $this->permissionRepo->getInfo($permissionId);
            if ($permission === []) {
                throw new CommonException('权限不存在');
            }

            if (! empty($permission['pid'])) {
                $this->ensureParentPermissionExists($roleId, (int) $permission['pid']);
            }

            $existing = $this->rolePermissionModel->newQuery()
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->first();

            if ($existing) {
                return (int) $existing->id;
            }

            return (int) $this->rolePermissionModel->create([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ])->id;
        });
    }

    public function deleteByRoleId(int $roleId): bool
    {
        $this->rolePermissionModel->newQuery()->where('role_id', $roleId)->delete();
        return true;
    }

    private function ensureParentPermissionExists(int $roleId, int $parentPermissionId): void
    {
        $exists = $this->rolePermissionModel->newQuery()
            ->where('role_id', $roleId)
            ->where('permission_id', $parentPermissionId)
            ->exists();

        if (! $exists) {
            $this->rolePermissionModel->create([
                'role_id' => $roleId,
                'permission_id' => $parentPermissionId,
            ]);
        }
    }
}
