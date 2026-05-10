<?php

declare(strict_types=1);

namespace Antmin\Http\Repository;

use Antmin\Model\AccountRole;

class AccountRoleRepository
{
    public function __construct(private readonly AccountRole $accountRoleModel)
    {
    }

    public function isHasAccountByRoleId(int $roleId): bool
    {
        return $this->accountRoleModel->newQuery()->where('role_id', $roleId)->first() !== null;
    }
}
