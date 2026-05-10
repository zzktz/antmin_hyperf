<?php

declare(strict_types=1);

namespace Antmin\Support;

use Antmin\Contract\PasswordHasherInterface;

class PasswordHasher implements PasswordHasherInterface
{
    public function hash(string $value): string
    {
        return password_hash($value, PASSWORD_BCRYPT);
    }

    public function check(string $value, string $hashedValue): bool
    {
        return password_verify($value, $hashedValue);
    }
}
