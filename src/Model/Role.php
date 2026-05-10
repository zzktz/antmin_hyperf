<?php

declare(strict_types=1);

namespace Antmin\Model;

use Hyperf\DbConnection\Model\Model;

class Role extends Model
{
    protected ?string $table = 'system_role';

    protected array $guarded = [];

    public function getRolesByAccountId(int $accountId, array $column): array
    {
        $roleIds = $this->getRolesIdsByAccountId($accountId);
        return self::query()->whereIn('id', $roleIds)->get($column)->toArray();
    }

    public function getRolesIdsByAccountId(int $accountId): array
    {
        if ($accountId === 1) {
            return [1];
        }

        return array_values(array_unique(
            AccountRole::query()->where('account_id', $accountId)->pluck('role_id')->toArray()
        ));
    }

    public function getSupperRoleId(): int
    {
        return 1;
    }
}
