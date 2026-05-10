<?php

declare(strict_types=1);

namespace Antmin\Model;

use Hyperf\DbConnection\Model\Model;

class OperateLog extends Model
{
    protected ?string $table = 'system_operate_log';

    protected array $guarded = ['id'];
}
