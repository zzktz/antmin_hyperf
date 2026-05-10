<?php

declare(strict_types=1);

return [
    'name' => 'Ant Admin',
    'route_prefix' => 'api/adminconsole',
    'token' => [
        'issuer' => 'antmin',
        'audience' => 'antmin-admin',
        'secret' => 'change-me',
        'expire_seconds' => 60 * 60 * 24 * 30,
        'max_tokens_per_user' => 3,
        'redis_prefix' => 'account_tokens:',
        'role_claim' => 'antadmin',
    ],
    'upload' => [
        'url' => '',
        'disk' => 'default',
    ],
    'cache' => [
        'prefix' => 'antmin:',
    ],
];
