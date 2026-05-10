<?php

declare(strict_types=1);

namespace Antmin\Model;

use Hyperf\DbConnection\Model\Model;

class MenuPermission extends Model
{
    protected ?string $table = 'system_menu_permission';

    protected array $guarded = [];
}
