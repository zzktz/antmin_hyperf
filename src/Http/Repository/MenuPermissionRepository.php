<?php

declare(strict_types=1);

namespace Antmin\Http\Repository;

use Antmin\Model\MenuPermission;

class MenuPermissionRepository
{
    public function __construct(private readonly MenuPermission $menuPermissionModel)
    {
    }

    public function getMenuIdsByPermissionIds(array $permissionIds): array
    {
        $defaultIds = [1, 2, 10, 16];
        if ($permissionIds === []) {
            return $defaultIds;
        }

        $res = $this->menuPermissionModel->newQuery()
            ->whereIn('permission_id', $permissionIds)
            ->pluck('menu_id')
            ->toArray();

        if ($res === []) {
            return $defaultIds;
        }

        return array_values(array_unique(array_merge($res, $defaultIds)));
    }

    public function getPermissionIdsByMenuId(int $menuId): array
    {
        return $this->menuPermissionModel->newQuery()
            ->where('menu_id', $menuId)
            ->pluck('permission_id')
            ->toArray();
    }

    public function replaceMenuPermissions(int $menuId, array $permissionIds): void
    {
        $this->menuPermissionModel->newQuery()->where('menu_id', $menuId)->delete();
        foreach ($permissionIds as $permissionId) {
            $this->menuPermissionModel->create([
                'menu_id' => $menuId,
                'permission_id' => $permissionId,
            ]);
        }
    }
}
