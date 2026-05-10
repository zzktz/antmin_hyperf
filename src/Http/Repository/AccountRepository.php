<?php

declare(strict_types=1);

namespace Antmin\Http\Repository;

use Antmin\Common\Base;
use Antmin\Exceptions\CommonException;
use Antmin\Model\Account;
use Antmin\Model\AccountRole;
use Hyperf\DbConnection\Db;

class AccountRepository
{
    private const SUPPER_ADMIN_ID = 1;

    public function __construct(
        private readonly Base $base,
        private readonly Account $accountModel,
        private readonly AccountRole $accountRoleModel,
        private readonly RoleRepository $roleRepository,
        private readonly PermissionRepository $permissionRepository,
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
            $rest[$k]['id'] = $v['id'];
            $rest[$k]['name'] = $v['name'];
            $rest[$k]['username'] = $v['nickname'];
            $rest[$k]['mobile'] = $v['mobile'];
            $rest[$k]['email'] = $v['email'];
            $rest[$k]['birthday'] = $v['birthday'];
            $rest[$k]['status'] = $v['status'];
            $rest[$k]['rolesData'] = $this->roleRepository->getRolesByAccountId((int) $v['id'], ['id', 'name']);
            $rest[$k]['avatar'] = !empty($v['avatar']) ? $this->base->fillUrl($v['avatar']) : '';
            $rest[$k]['roles'] = $this->roleRepository->getRolesIdsByAccountId((int) $v['id']);
            $rest[$k]['rules'] = $this->permissionRepository->getAllPermissionsIdsByAccountId((int) $v['id']);
            $rest[$k]['isShowDelete'] = $this->isSuperAdmin((int) $v['id']) ? 0 : 1;
            $rest[$k]['created_at'] = $v['created_at'];
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
        $query = $this->accountModel->newQuery()->orderByDesc('id');
        return $this->base->listFormat($limit, $query);
    }

    public function add(array $info): int
    {
        try {
            return Db::transaction(function () use ($info) {
                $password = $info['password'] !== '' ? $info['password'] : md5(bin2hex(random_bytes(6)));
                $randomName = $this->base->random(8, 'abcdefghijklmnopqrstuvwyz');
                $userData = [
                    'name' => $info['name'] ?? $randomName,
                    'nickname' => $info['nickname'] ?? $randomName,
                    'mobile' => $info['mobile'] ?? null,
                    'email' => $info['email'],
                    'password' => $password,
                ];

                $account = $this->accountModel->create($userData);
                foreach ($info['roles'] as $roleId) {
                    $this->accountRoleModel->create([
                        'account_id' => $account->id,
                        'role_id' => $roleId,
                    ]);
                }

                return (int) $account->id;
            });
        } catch (\Throwable $throwable) {
            throw new CommonException('添加用户失败: ' . $throwable->getMessage());
        }
    }

    public function editStatus(int $status, int $id): bool
    {
        $one = $this->accountModel->find($id);
        return (bool) $one?->update(['status' => $status]);
    }

    public function edit(array $info, int $id): bool
    {
        $one = $this->accountModel->find($id);
        return (bool) $one?->update($info);
    }

    public function editRole(array $info, int $id): bool
    {
        $this->accountRoleModel->newQuery()->where('account_id', $id)->delete();
        $roles = $info['roles'] ?? [];
        foreach ($roles as $roleId) {
            $this->accountRoleModel->create([
                'account_id' => $id,
                'role_id' => $roleId,
            ]);
        }
        return true;
    }

    public function del(int $id): void
    {
        $one = $this->accountModel->find($id);
        $one?->delete();
        $this->accountRoleModel->newQuery()->where('account_id', $id)->delete();
    }

    public function updateAvatar(string $avatar, int $accountId): bool
    {
        return (bool) $this->accountModel->newQuery()->where('id', $accountId)->update(['avatar' => $avatar]);
    }

    public function updatePassword(string $password, int $accountId): bool
    {
        return (bool) $this->accountModel->newQuery()->where('id', $accountId)->update(['password' => $password]);
    }

    public function getInfoByName(string $name): array
    {
        return $this->toArray($this->accountModel->newQuery()->where('name', $name)->first());
    }

    public function getInfoByMobile(string $mobile): array
    {
        return $this->toArray($this->accountModel->newQuery()->where('mobile', $mobile)->first());
    }

    public function getInfoByEmail(string $email): array
    {
        return $this->toArray($this->accountModel->newQuery()->where('email', $email)->first());
    }

    public function getInfo(int $accountId): array
    {
        return $this->toArray($this->accountModel->newQuery()->where('id', $accountId)->first());
    }

    public function findByField(string $field, string $value): array
    {
        return $this->toArray($this->accountModel->newQuery()->where($field, $value)->first());
    }

    public function isSuperAdmin(int $accountId): bool
    {
        return $accountId === self::SUPPER_ADMIN_ID;
    }

    private function toArray(mixed $model): array
    {
        return $model ? $model->toArray() : [];
    }
}
