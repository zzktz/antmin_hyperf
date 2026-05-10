<?php

declare(strict_types=1);

namespace Antmin\Model;

use Hyperf\DbConnection\Model\Model;

class RolePermission extends Model
{
    protected ?string $table = 'system_role_permission';

    protected array $guarded = ['id'];
}
