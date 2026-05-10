<?php

declare(strict_types=1);

namespace Antmin\Support;

use Hyperf\Context\RequestContext;
use Hyperf\Context\ResponseContext;
use Hyperf\Contract\ContainerInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;

class HyperfContext
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function request(): ServerRequestInterface
    {
        $request = RequestContext::getOrNull();
        if ($request !== null) {
            return $request;
        }

        return $this->container->get(RequestInterface::class);
    }

    public function response(): ResponseInterface
    {
        $response = ResponseContext::getOrNull();
        if ($response !== null) {
            return $response;
        }

        return $this->container->get(ResponseInterface::class);
    }

    public function config(string $key, mixed $default = null): mixed
    {
        try {
            $config = $this->container->get(\Hyperf\Contract\ConfigInterface::class);
        } catch (ContainerExceptionInterface) {
            return $default;
        }

        return $config->get($key, $default);
    }
}
