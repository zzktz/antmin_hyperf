<?php

declare(strict_types=1);

namespace Antmin\Http\Repository;

use Antmin\Common\Base;
use Antmin\Model\Role;
use Antmin\Model\AccountRole;

class RoleRepository
{
    public function __construct(
        private readonly Role $roleModel,
        private readonly AccountRole $accountRoleModel,
        private readonly PermissionRepository $permissionRepo,
        private readonly Base $base,
    ) {
    }

    public function getFormatList(int $limit): array
    {
        $datas = $this->getList($limit);
        if (empty($datas['data'])) {
            return $datas;
        }

        $rest = [];
        foreach ($datas['data'] as $k => $v) {
            $permissions = $this->permissionRepo->getAllPermissionsIdsByRoleIds([(int) $v['id']]);
            $rest[$k] = $v;
            $rest[$k]['permissions'] = $permissions;
            $rest[$k]['isShowDelete'] = (int) $v['id'] === 1 ? 0 : 1;
        }

        return [
            'pagination' => [
                'current' => $datas['pageNo'],
                'pageSize' => $datas['pageSize'],
                'total' => $datas['totalCount'],
            ],
            'data' => $rest,
        ];
    }

    public function getFormatAccountList(int $limit): array
    {
        $datas = $this->getList($limit);
        if (empty($datas['data'])) {
            return $datas;
        }

        $rest = [];
        foreach ($datas['data'] as $k => $v) {
            $permissions = $this->permissionRepo->getAllPermissionsIdsByRoleIds([(int) $v['id']]);
            $rest[$k]['id'] = $v['id'];
            $rest[$k]['name'] = $v['vid'];
            $rest[$k]['title'] = $v['name'];
            $rest[$k]['status'] = $v['status'];
            $rest[$k]['permissionId'] = $permissions;
        }

        return [
            'pagination' => [
                'current' => $datas['pageNo'],
                'pageSize' => $datas['pageSize'],
                'total' => $datas['totalCount'],
            ],
            'data' => $rest,
        ];
    }

    public function getList(int $limit): array
    {
        $query = $this->roleModel->newQuery()->orderBy('id');
        return $this->base->listFormat($limit, $query);
    }

    public function add(array $info): int
    {
        return (int) $this->roleModel->create([
            'vid' => $info['vid'],
            'name' => $info['name'],
        ])->id;
    }

    public function edit(array $info, int $id): bool
    {
        $one = $this->roleModel->find($id);
        return (bool) $one?->update($info);
    }

    public function del(int $id): bool
    {
        $one = $this->roleModel->find($id);
        return (bool) $one?->delete();
    }

    public function getInfo(int $id): array
    {
        return $this->toArray($this->roleModel->find($id));
    }

    public function getRoleNameByAccountId(int $accountId): string
    {
        $arr = $this->roleModel->getRolesByAccountId($accountId, ['name']);
        return $arr[0]['name'] ?? '';
    }

    public function getRolesByAccountId(int $accountId, array $column): array
    {
        return $this->roleModel->getRolesByAccountId($accountId, $column);
    }

    public function getRolesIdsByAccountId(int $accountId): array
    {
        return $this->roleModel->getRolesIdsByAccountId($accountId);
    }

    public function getInfoByName(string $name): array
    {
        return $this->toArray($this->roleModel->newQuery()->where('name', $name)->first());
    }

    public function getInfoByVid(string $vid): array
    {
        return $this->toArray($this->roleModel->newQuery()->where('vid', $vid)->first());
    }

    public function getSupperRoleId(): int
    {
        return $this->roleModel->getSupperRoleId();
    }

    private function toArray(mixed $model): array
    {
        return $model ? $model->toArray() : [];
    }
}
