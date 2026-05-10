<?php

declare(strict_types=1);

namespace Antmin\Model;

use Hyperf\DbConnection\Model\Model;

class Permission extends Model
{
    protected ?string $table = 'system_permission';

    protected array $guarded = [];
}
