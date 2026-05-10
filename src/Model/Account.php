<?php

declare(strict_types=1);

namespace Antmin\Model;

use Hyperf\DbConnection\Model\Model;

class Account extends Model
{
    protected ?string $table = 'system_account';

    protected array $guarded = ['id'];
}
