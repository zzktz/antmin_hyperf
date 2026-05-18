<?php

declare(strict_types=1);

namespace AntminTest\Unit\Http\Service;

use Antmin\Common\Base;
use Antmin\Contract\PasswordHasherInterface;
use Antmin\Exceptions\CommonException;
use Antmin\Http\Repository\AccountRepository;
use Antmin\Http\Repository\SmsRepository;
use Antmin\Http\Repository\TokenRepository;
use Antmin\Http\Service\EmailService;
use Antmin\Http\Service\LoginService;
use Antmin\Http\Service\PasswordService;
use Antmin\Http\Service\SafeService;
use PHPUnit\Framework\TestCase;

class LoginServiceTest extends TestCase
{
    public function testAccountLoginRejectsDisabledAccount(): void
    {
        $accountRepo = $this->createMock(AccountRepository::class);
        $accountRepo->expects($this->once())
            ->method('getInfoByName')
            ->with('admin')
            ->willReturn([
                'id' => 7,
                'password' => 'stored-password',
                'status' => 0,
            ]);

        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $passwordHasher->expects($this->once())
            ->method('check')
            ->with('secret', 'stored-password')
            ->willReturn(true);

        $base = $this->createMock(Base::class);
        $base->expects($this->once())
            ->method('isEmail')
            ->with('admin')
            ->willReturn(false);

        $safeService = $this->createMock(SafeService::class);
        $safeService->expects($this->once())->method('checking');
        $safeService->expects($this->never())->method('flagSuccess');
        $safeService->expects($this->once())->method('flagFail')->willReturn(1);
        $safeService->expects($this->once())->method('getMaxTip')->willReturn('已记录本次失败');

        $tokenRepo = $this->createMock(TokenRepository::class);
        $tokenRepo->expects($this->never())->method('getTokenById');

        $service = $this->createService(
            accountRepo: $accountRepo,
            tokenRepo: $tokenRepo,
            passwordHasher: $passwordHasher,
            base: $base,
            safeService: $safeService,
        );

        try {
            $service->accountLogin('admin', 'secret');
            $this->fail('Expected CommonException to be thrown.');
        } catch (CommonException $exception) {
            $this->assertStringContainsString('账号已被禁用，请联系管理员', $exception->getMessage());
        }
    }

    public function testMobileLoginRejectsDisabledAccount(): void
    {
        $smsRepo = $this->createMock(SmsRepository::class);
        $smsRepo->expects($this->once())
            ->method('checkSmsCode')
            ->with('13800138000', '123456', true)
            ->willReturn(true);

        $accountRepo = $this->createMock(AccountRepository::class);
        $accountRepo->expects($this->once())
            ->method('getInfoByMobile')
            ->with('13800138000')
            ->willReturn([
                'id' => 7,
                'status' => 0,
            ]);

        $tokenRepo = $this->createMock(TokenRepository::class);
        $tokenRepo->expects($this->never())->method('getTokenById');

        $service = $this->createService(
            accountRepo: $accountRepo,
            tokenRepo: $tokenRepo,
            smsRepo: $smsRepo,
        );

        $this->expectException(CommonException::class);
        $this->expectExceptionMessage('账号已被禁用，请联系管理员');

        $service->mobileLogin('13800138000', '123456');
    }

    private function createService(
        ?AccountRepository $accountRepo = null,
        ?TokenRepository $tokenRepo = null,
        ?SmsRepository $smsRepo = null,
        ?PasswordHasherInterface $passwordHasher = null,
        ?PasswordService $passwordService = null,
        ?Base $base = null,
        ?SafeService $safeService = null,
        ?EmailService $emailService = null,
    ): LoginService {
        return new LoginService(
            $accountRepo ?? $this->createMock(AccountRepository::class),
            $tokenRepo ?? $this->createMock(TokenRepository::class),
            $smsRepo ?? $this->createMock(SmsRepository::class),
            $passwordHasher ?? $this->createMock(PasswordHasherInterface::class),
            $passwordService ?? $this->createMock(PasswordService::class),
            $base ?? $this->createMock(Base::class),
            $safeService ?? $this->createMock(SafeService::class),
            $emailService ?? $this->createMock(EmailService::class),
        );
    }
}
