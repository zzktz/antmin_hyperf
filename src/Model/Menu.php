<?php

declare(strict_types=1);

namespace Antmin\Model;

use Hyperf\DbConnection\Model\Model;

class Menu extends Model
{
    protected ?string $table = 'system_menu';

    protected array $guarded = [];
}
