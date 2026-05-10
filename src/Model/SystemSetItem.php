<?php

declare(strict_types=1);

namespace Antmin\Model;

use Hyperf\DbConnection\Model\Model;

class SystemSetItem extends Model
{
    protected ?string $table = 'system_set_item';

    protected array $guarded = ['id'];
}
