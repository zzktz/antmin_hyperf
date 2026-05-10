<?php

declare(strict_types=1);

namespace Antmin;

use Antmin\Contract\FileStorageInterface;
use Antmin\Contract\PasswordHasherInterface;
use Antmin\Contract\TokenServiceInterface;
use Antmin\Exception\Handler\CommonExceptionHandler;
use Antmin\Listener\RegisterRoutesListener;
use Antmin\Support\FileStorage;
use Antmin\Support\PasswordHasher;
use Antmin\Token\JwtTokenService;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                TokenServiceInterface::class => JwtTokenService::class,
                PasswordHasherInterface::class => PasswordHasher::class,
                FileStorageInterface::class => FileStorage::class,
            ],
            'exceptions' => [
                'handler' => [
                    'http' => [
                        CommonExceptionHandler::class,
                    ],
                ],
            ],
            'listeners' => [
                RegisterRoutesListener::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for antmin.',
                    'source' => __DIR__ . '/../publish/antmin.php',
                    'destination' => BASE_PATH . '/config/autoload/antmin.php',
                ],
            ],
        ];
    }
}
