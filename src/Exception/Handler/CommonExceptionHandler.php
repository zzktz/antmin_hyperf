<?php

declare(strict_types=1);

namespace Antmin\Exception\Handler;

use Antmin\Common\Base;
use Antmin\Exceptions\CommonException;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Swow\Psr7\Message\ResponsePlusInterface;
use Throwable;

class CommonExceptionHandler extends ExceptionHandler
{
    public function __construct(private readonly Base $base)
    {
    }

    public function handle(Throwable $throwable, ResponsePlusInterface $response)
    {
        if (! $throwable instanceof CommonException) {
            return $response;
        }

        $this->stopPropagation();

        return $this->base->errJson(
            $throwable->getMessage(),
            $throwable->getData(),
            $throwable->getCode(),
        )->withStatus($throwable->getStatusCode());
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof CommonException;
    }
}
