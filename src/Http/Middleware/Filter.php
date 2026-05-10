<?php

declare(strict_types=1);

namespace Antmin\Http\Middleware;

class Filter
{
    public static function getFilterMethod(): array
    {
        return [
            'systemRegister',
            'systemLogin',
            'systemResetPassword',
            'sendCodeByEmail',
            'systemUploadEditor',
        ];
    }
}
