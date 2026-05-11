<?php

declare(strict_types=1);

namespace Antmin\Listener;

use Antmin\Route\RouteRegistrar;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\HttpServer\Router\Router;

class RegisterRoutesListener implements ListenerInterface
{
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly DispatcherFactory $dispatcherFactory,
    ) {
    }

    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    public function process(object $event): void
    {
        Router::init($this->dispatcherFactory);
        $prefix = (string) $this->config->get('antmin.route_prefix', 'api/adminconsole');
        RouteRegistrar::register($prefix);
    }
}
