<?php

declare(strict_types=1);

namespace Antmin\Model;

use Hyperf\DbConnection\Model\Model;

class SystemSet extends Model
{
    protected ?string $table = 'system_set';

    protected array $guarded = ['id'];
}
