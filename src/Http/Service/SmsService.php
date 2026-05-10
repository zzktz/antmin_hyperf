<?php

declare(strict_types=1);

namespace Antmin\Http\Service;

use Antmin\Exceptions\CommonException;
use Antmin\Http\Repository\AccountRepository;
use Antmin\Http\Repository\SmsRepository;

class SmsService
{
    public function __construct(
        private readonly AccountRepository $accountRepo,
        private readonly SmsRepository $smsRepository,
    ) {
    }

    public function sendSmsCode(string $mobile, string $ip, string $type): bool
    {
        if (! preg_match('/^1[3-9]\d{9}$/', $mobile)) {
            throw new CommonException('手机号不正确');
        }

        if (! in_array($type, $this->smsRepository->getSendType(), true)) {
            throw new CommonException('发送类型不正确');
        }

        $one = $this->accountRepo->getInfoByMobile($mobile);
        if (in_array($type, ['login', 'fixPassword', 'forgetPassword'], true) && $one === []) {
            throw new CommonException('手机号未注册');
        }
        if ($type === 'reg' && $one !== []) {
            throw new CommonException('手机号已注册');
        }

        $this->smsRepository->sendSmsCode($mobile, $ip);
        return true;
    }
}
