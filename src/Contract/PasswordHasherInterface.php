<?php

declare(strict_types=1);

namespace Antmin\Contract;

interface PasswordHasherInterface
{
    public function hash(string $value): string;

    public function check(string $value, string $hashedValue): bool;
}
