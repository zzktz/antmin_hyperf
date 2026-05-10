<?php

declare(strict_types=1);

namespace Antmin\Model;

use Hyperf\DbConnection\Model\Model;

class SystemSetDetail extends Model
{
    protected ?string $table = 'system_set_detail';

    protected array $guarded = ['id'];
}
