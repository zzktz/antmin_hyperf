<?php

declare(strict_types=1);

namespace Antmin\Exceptions;

use Exception;

class CommonException extends Exception
{
    public function __construct(
        string $message,
        private readonly array $data = [],
        int $code = 0,
        private readonly int $statusCode = 200,
    ) {
        parent::__construct($message, $code);
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
