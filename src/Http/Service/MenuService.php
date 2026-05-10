<?php

declare(strict_types=1);

namespace Antmin\Http\Service;

use Antmin\Exceptions\CommonException;
use Antmin\Http\Repository\AccountRepository;
use Antmin\Http\Repository\MenuPermissionRepository;
use Antmin\Http\Repository\MenuRepository;
use Antmin\Http\Repository\RolePermissionsRepository;
use Antmin\Http\Repository\RoleRepository;
use Antmin\Model\Menu;

class MenuService
{
    public function __construct(
        private readonly Menu $menuModel,
        private readonly AccountRepository $accountRepo,
        private readonly RoleRepository $roleRepo,
        private readonly RolePermissionsRepository $rolePermissionsRepo,
        private readonly MenuRepository $menuRepo,
        private readonly MenuPermissionRepository $menuPermissionRepo,
    ) {
    }

    public function getMenuNav(int $accountId): array
    {
        $roleIds = $this->roleRepo->getRolesIdsByAccountId($accountId);
        $permissionIds = $this->rolePermissionsRepo->getPermissionsIdsByRoleIds($roleIds);
        $menuIds = $this->menuPermissionRepo->getMenuIdsByPermissionIds($permissionIds);
        $query = $this->menuModel->newQuery();

        if ($this->accountRepo->isSuperAdmin($accountId)) {
            $query->where('id', '>', 0);
        } else {
            $query->whereIn('id', $menuIds);
        }

        $data = $query->orderBy('listorder')->orderByDesc('id')->get()->toArray();
        if ($data === []) {
            return [];
        }

        $res = [];
        foreach ($data as $k => $v) {
            $res[$k] = [
                'id' => $v['id'],
                'name' => $v['page_name'],
                'component' => $v['component'],
                'path' => $v['route_path'],
                'parentId' => $v['parent_id'],
                'redirect' => $v['redirect'],
                'isHideChildren' => $v['is_hide_children'],
                'meta' => [
                    'title' => $v['title'],
                    'icon' => $v['icon'],
                    'hidden' => empty($v['is_show']),
                    'hideChildren' => (bool) $v['is_hide_children'],
                    'permission' => [],
                ],
            ];
        }

        return $res;
    }

    public function menuList(int $parentId, array $parent = []): array
    {
        $allData = $this->menuRepo->getAllCacheData();
        return $this->buildMenuTree($allData, $parentId, $parent);
    }

    public function menuAdd(array $info, int $accountId): int
    {
        $this->checkPermissions($accountId);
        $resId = $this->menuRepo->add([
            'parent_id' => $info['parentId'],
            'title' => $info['title'],
            'icon' => $info['icon'],
            'page_name' => $info['pageName'],
            'route_path' => $info['routePath'],
            'component' => $info['component'],
            'redirect' => $info['redirect'],
        ]);
        $this->menuPermissionRepo->replaceMenuPermissions($resId, $info['permissionIds'] ?? []);
        return $resId;
    }

    public function menuEdit(array $info, int $id, int $accountId): bool
    {
        $this->checkPermissions($accountId);
        if ($this->menuRepo->getInfo($id) === []) {
            throw new CommonException('菜单信息不存在');
        }

        $this->menuPermissionRepo->replaceMenuPermissions($id, $info['permissionIds'] ?? []);

        return $this->menuRepo->edit([
            'parent_id' => $info['parentId'],
            'title' => $info['title'],
            'icon' => $info['icon'],
            'page_name' => $info['pageName'],
            'route_path' => $info['routePath'],
            'component' => $info['component'],
            'redirect' => $info['redirect'],
        ], $id);
    }

    public function menuDel(int $id, int $accountId): bool
    {
        $this->checkPermissions($accountId);
        if ($this->menuRepo->getDataByParentId($id) !== []) {
            throw new CommonException('有子级不可删除');
        }
        return $this->menuRepo->del($id);
    }

    public function menuEditListorder(int $listorder, int $id, int $accountId): bool
    {
        $this->checkPermissions($accountId);
        if ($this->menuRepo->getInfo($id) === []) {
            throw new CommonException('菜单信息不存在');
        }
        return $this->menuRepo->edit(['listorder' => $listorder], $id);
    }

    public function menuEditIsShow(int $id, int $accountId): bool
    {
        $this->checkPermissions($accountId);
        $one = $this->menuRepo->getInfo($id);
        if ($one === []) {
            throw new CommonException('菜单信息不存在');
        }
        return $this->menuRepo->edit(['is_show' => empty($one['is_show']) ? 1 : 0], $id);
    }

    public function menuEditIsHideChildren(int $id, int $accountId): bool
    {
        $this->checkPermissions($accountId);
        $one = $this->menuRepo->getInfo($id);
        if ($one === []) {
            throw new CommonException('菜单信息不存在');
        }
        return $this->menuRepo->edit(['is_hide_children' => empty($one['is_hide_children']) ? 1 : 0], $id);
    }

    private function buildMenuTree(array $allData, int $parentId, array $parent = []): array
    {
        $result = [];
        foreach ($allData as $item) {
            if ((int) ($item['parent_id'] ?? 0) !== $parentId) {
                continue;
            }

            $child = $this->buildMenuTree($allData, (int) $item['id'], $item);
            $pid = (int) ($item['parent_id'] ?? 0);
            $result[] = [
                'id' => $item['id'],
                'title' => $item['title'],
                'icon' => $item['icon'],
                'component' => $item['component'],
                'pageName' => $item['page_name'],
                'routePath' => $item['route_path'],
                'listorder' => $item['listorder'],
                'key' => $pid . '-' . $item['id'],
                'value' => $pid . '-' . $item['id'],
                'parentId' => $pid,
                'parentName' => $parent !== [] ? ($parent['title'] ?? '顶级') : '顶级',
                'isChild' => $child !== [] ? 1 : 0,
                'children' => $child,
                'isDelete' => (int) $item['id'] < 20 ? 0 : 1,
                'isShow' => $item['is_show'],
                'redirect' => $item['redirect'],
                'roles' => $this->menuPermissionRepo->getPermissionIdsByMenuId((int) $item['id']),
                'isHideChildren' => $item['is_hide_children'],
            ];
        }

        usort($result, static fn (array $a, array $b): int => ($a['listorder'] <=> $b['listorder']) ?: ($b['id'] <=> $a['id']));
        return $result;
    }

    private function checkPermissions(int $accountId): void
    {
        if (! $this->accountRepo->isSuperAdmin($accountId)) {
            throw new CommonException('非超级管理员无权操作');
        }
    }
}
