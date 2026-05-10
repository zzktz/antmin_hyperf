<?php

declare(strict_types=1);

namespace Antmin\Model;

use Hyperf\DbConnection\Model\Model;

class AccountRole extends Model
{
    protected ?string $table = 'system_account_role';

    protected array $guarded = [];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id')->select(['id', 'name', 'vid']);
    }
}
