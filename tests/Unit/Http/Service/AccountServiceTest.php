<?php

declare(strict_types=1);

namespace AntminTest\Unit\Http\Service;

use Antmin\Common\Base;
use Antmin\Contract\PasswordHasherInterface;
use Antmin\Exceptions\CommonException;
use Antmin\Http\Repository\AccountRepository;
use Antmin\Http\Repository\AccountRoleRepository;
use Antmin\Http\Repository\PermissionRepository;
use Antmin\Http\Repository\RoleRepository;
use Antmin\Http\Repository\TokenRepository;
use Antmin\Http\Service\AccountService;
use Antmin\Http\Service\PasswordService;
use PHPUnit\Framework\TestCase;

class AccountServiceTest extends TestCase
{
    public function testGetAccountIdByTokenRejectsDisabledAccount(): void
    {
        $accountRepo = $this->createMock(AccountRepository::class);
        $accountRepo->expects($this->once())
            ->method('getInfo')
            ->with(7)
            ->willReturn(['id' => 7, 'status' => 0]);

        $tokenRepo = $this->createMock(TokenRepository::class);
        $tokenRepo->expects($this->once())
            ->method('getIdByToken')
            ->with('token-123')
            ->willReturn(7);

        $service = $this->createService(
            accountRepo: $accountRepo,
            tokenRepo: $tokenRepo,
        );

        try {
            $service->getAccountIdByToken('token-123');
            $this->fail('Expected CommonException to be thrown.');
        } catch (CommonException $exception) {
            $this->assertSame('账号已被禁用，请联系管理员', $exception->getMessage());
            $this->assertSame(401, $exception->getStatusCode());
        }
    }

    public function testReInitPasswordRejectsNonSuperAdmin(): void
    {
        $accountRepo = $this->createMock(AccountRepository::class);
        $accountRepo->expects($this->once())
            ->method('isSuperAdmin')
            ->with(2)
            ->willReturn(false);

        $service = $this->createService(accountRepo: $accountRepo);

        $this->expectException(CommonException::class);
        $this->expectExceptionMessage('非超级管理员无权操作');

        $service->reInitPassword(7, 2);
    }

    public function testReInitPasswordAllowsSuperAdmin(): void
    {
        $accountRepo = $this->createMock(AccountRepository::class);
        $accountRepo->expects($this->once())
            ->method('isSuperAdmin')
            ->with(1)
            ->willReturn(true);
        $accountRepo->expects($this->once())
            ->method('getInfo')
            ->with(7)
            ->willReturn(['id' => 7, 'status' => 1]);
        $accountRepo->expects($this->once())
            ->method('updatePassword')
            ->with('hashed-default-password', 7)
            ->willReturn(true);

        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $passwordHasher->expects($this->once())
            ->method('hash')
            ->with('a@123456')
            ->willReturn('hashed-default-password');

        $service = $this->createService(
            accountRepo: $accountRepo,
            passwordHasher: $passwordHasher,
        );

        $this->assertTrue($service->reInitPassword(7, 1));
    }

    public function testLogoutRevokesCurrentToken(): void
    {
        $tokenRepo = $this->createMock(TokenRepository::class);
        $tokenRepo->expects($this->once())
            ->method('revokeToken')
            ->with(7, 'token-123');

        $service = $this->createService(tokenRepo: $tokenRepo);
        $service->logout(7, 'token-123');
    }

    private function createService(
        ?AccountRepository $accountRepo = null,
        ?RoleRepository $roleRepo = null,
        ?PermissionRepository $permissionRepo = null,
        ?TokenRepository $tokenRepo = null,
        ?AccountRoleRepository $accountRoleRepo = null,
        ?PasswordService $passwordService = null,
        ?PasswordHasherInterface $passwordHasher = null,
        ?Base $base = null,
    ): AccountService {
        return new AccountService(
            $accountRepo ?? $this->createMock(AccountRepository::class),
            $roleRepo ?? $this->createMock(RoleRepository::class),
            $permissionRepo ?? $this->createMock(PermissionRepository::class),
            $tokenRepo ?? $this->createMock(TokenRepository::class),
            $accountRoleRepo ?? $this->createMock(AccountRoleRepository::class),
            $passwordService ?? $this->createMock(PasswordService::class),
            $passwordHasher ?? $this->createMock(PasswordHasherInterface::class),
            $base ?? $this->createMock(Base::class),
        );
    }
}
