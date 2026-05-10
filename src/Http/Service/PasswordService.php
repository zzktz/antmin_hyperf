<?php

declare(strict_types=1);

namespace Antmin\Http\Service;

use Antmin\Exceptions\CommonException;

class PasswordService
{
    public function checkPasswordStrength(string $password): void
    {
        $length = mb_strlen($password, 'UTF-8');
        if ($length < 6 || $length > 16) {
            throw new CommonException('密码长度应为6~16个字符');
        }

        $types = 0;
        if (preg_match('/[a-z]/', $password) === 1) {
            ++$types;
        }
        if (preg_match('/[A-Z]/', $password) === 1) {
            ++$types;
        }
        if (preg_match('/\d/', $password) === 1) {
            ++$types;
        }
        if (preg_match('/[^a-zA-Z\d]/', $password) === 1) {
            ++$types;
        }

        if ($types < 3) {
            throw new CommonException('密码应为字母、数字、特殊符号组合，6~16个字符');
        }
    }
}
