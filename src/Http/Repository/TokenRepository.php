<?php

declare(strict_types=1);

namespace Antmin\Http\Repository;

use Antmin\Contract\TokenServiceInterface;

class TokenRepository
{
    public function __construct(private readonly TokenServiceInterface $tokenService)
    {
    }

    public function getIdByToken(string $token): int
    {
        return $this->tokenService->parseToken($token);
    }

    public function getTokenById(int $accountId): string
    {
        return $this->tokenService->issueToken($accountId);
    }
}
