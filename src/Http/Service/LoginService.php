<?php

declare(strict_types=1);

namespace Antmin\Http\Service;

use Antmin\Common\Base;
use Antmin\Contract\PasswordHasherInterface;
use Antmin\Exceptions\CommonException;
use Antmin\Http\Repository\AccountRepository;
use Antmin\Http\Repository\SmsRepository;
use Antmin\Http\Repository\TokenRepository;

class LoginService
{
    public function __construct(
        private readonly AccountRepository $accountRepo,
        private readonly TokenRepository $tokenRepo,
        private readonly SmsRepository $smsRepo,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly PasswordService $passwordService,
        private readonly Base $base,
        private readonly SafeService $safeService,
        private readonly EmailService $emailService,
    ) {
    }

    public function accountLogin(string $name, string $password): string
    {
        $this->safeService->checking();

        try {
            $info = $this->base->isEmail($name)
                ? $this->accountRepo->getInfoByEmail($name)
                : $this->accountRepo->getInfoByName($name);

            if ($info === [] || ! $this->passwordHasher->check($password, (string) ($info['password'] ?? ''))) {
                throw new CommonException('账户或密码错误');
            }

            $this->ensureAccountIsActive($info);
            $token = $this->tokenRepo->getTokenById((int) $info['id']);
            $this->safeService->flagSuccess();
            return $token;
        } catch (CommonException $exception) {
            $num = $this->safeService->flagFail();
            throw new CommonException('第' . $num . '次' . $exception->getMessage() . '，' . $this->safeService->getMaxTip());
        }
    }

    public function register(string $email, string $code, string $password): string
    {
        if ($this->accountRepo->getInfoByEmail($email) !== []) {
            throw new CommonException('邮箱已注册');
        }
        if (! $this->emailService->verifyCode($email, $code)) {
            throw new CommonException('邮箱验证码不正确');
        }

        $this->passwordService->checkPasswordStrength($password);
        $accountId = $this->accountRepo->add([
            'email' => $email,
            'password' => $this->passwordHasher->hash($password),
            'roles' => [4],
        ]);

        return $this->tokenRepo->getTokenById($accountId);
    }

    public function sendCodeByEmail(string $email, string $type): bool
    {
        $info = $this->accountRepo->getInfoByEmail($email);
        if ($type === 'forget') {
            if ($info === []) {
                throw new CommonException('邮箱未注册');
            }
        } elseif ($info !== []) {
            throw new CommonException('邮箱已注册');
        }

        return $this->emailService->sendCode($email);
    }

    public function mobileLogin(string $mobile, string $smscode): string
    {
        $res = $this->smsRepo->checkSmsCode($mobile, $smscode, true);
        if (! $res) {
            throw new CommonException('短信验证码错误');
        }

        $info = $this->accountRepo->getInfoByMobile($mobile);
        if ($info === []) {
            throw new CommonException('手机号不存在');
        }

        $this->ensureAccountIsActive($info);
        return $this->tokenRepo->getTokenById((int) $info['id']);
    }

    public function systemResetPassword(string $email, string $password, string $code): bool
    {
        $info = $this->accountRepo->getInfoByEmail($email);
        if ($info === []) {
            throw new CommonException('邮箱未注册');
        }
        if (! $this->emailService->verifyCode($email, $code)) {
            throw new CommonException('邮箱验证码不正确');
        }

        $this->passwordService->checkPasswordStrength($password);
        return $this->accountRepo->updatePassword($this->passwordHasher->hash($password), (int) $info['id']);
    }

    private function ensureAccountIsActive(array $account): void
    {
        if ((int) ($account['status'] ?? 0) !== 1) {
            throw new CommonException('账号已被禁用，请联系管理员');
        }
    }
}
