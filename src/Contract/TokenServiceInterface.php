<?php

declare(strict_types=1);

namespace Antmin\Contract;

interface TokenServiceInterface
{
    public function issueToken(int $accountId): string;

    public function parseToken(string $token): int;
}
