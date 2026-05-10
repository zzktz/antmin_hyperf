<?php

declare(strict_types=1);

namespace AntminTest\Unit\Http\Controller;

use Antmin\Common\Base;
use Antmin\Exceptions\CommonException;
use Antmin\Http\Controller\AccountController;
use Antmin\Http\Controller\EnterController;
use Antmin\Http\Controller\MenuController;
use Antmin\Http\Controller\PermissionsController;
use Antmin\Http\Controller\RoleController;
use Antmin\Http\Service\AccountService;
use Antmin\Http\Service\LoginService;
use Antmin\Http\Service\PermissionsService;
use Hyperf\Contract\ValidatorInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AccountControllerTest extends TestCase
{
    public function testLoginRejectsEmptyUsername(): void
    {
        $controller = new AccountController(
            $this->createMock(AccountService::class),
            $this->createMock(LoginService::class),
            $this->createMock(Base::class),
            $this->createRequest(['username' => '']),
            $this->createMock(ValidatorFactoryInterface::class),
        );

        try {
            $controller->login();
            $this->fail('Expected CommonException to be thrown.');
        } catch (CommonException $exception) {
            $this->assertSame('登录失败: 登录账号/手机号/邮件地址不能空', $exception->getMessage());
        }
    }

    public function testLoginUsesMobileBranchForMobileUsername(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $base = $this->createMock(Base::class);
        $base->expects($this->once())->method('isMobile')->with('13800138000')->willReturn(true);
        $base->expects($this->once())
            ->method('sucJson')
            ->with('成功', ['token' => 'mobile-token'], 0)
            ->willReturn($response);

        $loginService = $this->createMock(LoginService::class);
        $loginService->expects($this->once())
            ->method('mobileLogin')
            ->with('13800138000', '123456')
            ->willReturn('mobile-token');
        $loginService->expects($this->never())->method('accountLogin');

        $validator = $this->createMock(ValidatorFactoryInterface::class);
        $validatorInstance = $this->createMock(ValidatorInterface::class);
        $validatorInstance->expects($this->once())
            ->method('fails')
            ->willReturn(false);
        $validator->expects($this->once())
            ->method('make')
            ->with(
                ['username' => '13800138000', 'captcha' => '123456'],
                ['captcha' => 'required|max:6'],
                [],
                []
            )
            ->willReturn($validatorInstance);

        $controller = new AccountController(
            $this->createMock(AccountService::class),
            $loginService,
            $base,
            $this->createRequest(['username' => '13800138000', 'captcha' => '123456']),
            $validator,
        );

        $this->assertSame($response, $controller->login());
    }

    public function testLoginUsesAccountBranchForNonMobileUsername(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $base = $this->createMock(Base::class);
        $base->expects($this->once())->method('isMobile')->with('admin@example.com')->willReturn(false);
        $base->expects($this->once())
            ->method('sucJson')
            ->with('成功', ['token' => 'account-token'], 0)
            ->willReturn($response);

        $loginService = $this->createMock(LoginService::class);
        $loginService->expects($this->once())
            ->method('accountLogin')
            ->with('admin@example.com', 'secret')
            ->willReturn('account-token');
        $loginService->expects($this->never())->method('mobileLogin');

        $controller = new AccountController(
            $this->createMock(AccountService::class),
            $loginService,
            $base,
            $this->createRequest(['username' => 'admin@example.com', 'password' => 'secret']),
            $this->createMock(ValidatorFactoryInterface::class),
        );

        $this->assertSame($response, $controller->login());
    }

    public function testRegisterDelegatesToLoginServiceAndReturnsToken(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $base = $this->createMock(Base::class);
        $base->expects($this->exactly(3))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls('user@example.com', '654321', 'aStrongPass1');
        $base->expects($this->once())
            ->method('sucJson')
            ->with('成功', ['token' => 'register-token'], 0)
            ->willReturn($response);

        $loginService = $this->createMock(LoginService::class);
        $loginService->expects($this->once())
            ->method('register')
            ->with('user@example.com', '654321', 'aStrongPass1')
            ->willReturn('register-token');

        $controller = new AccountController(
            $this->createMock(AccountService::class),
            $loginService,
            $base,
            $this->createRequest(['email' => 'user@example.com', 'captcha' => '654321', 'password' => 'aStrongPass1']),
            $this->createMock(ValidatorFactoryInterface::class),
        );

        $this->assertSame($response, $controller->register());
    }

    public function testSendCodeByEmailDelegatesToLoginService(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $base = $this->createMock(Base::class);
        $base->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls('user@example.com', 'forget');
        $base->expects($this->once())
            ->method('sucJson')
            ->with('邮件验证码已发送', [], 0)
            ->willReturn($response);

        $loginService = $this->createMock(LoginService::class);
        $loginService->expects($this->once())
            ->method('sendCodeByEmail')
            ->with('user@example.com', 'forget');

        $controller = new AccountController(
            $this->createMock(AccountService::class),
            $loginService,
            $base,
            $this->createRequest(['email' => 'user@example.com', 'type' => 'forget']),
            $this->createMock(ValidatorFactoryInterface::class),
        );

        $this->assertSame($response, $controller->sendCodeByEmail());
    }

    public function testSystemResetPasswordDelegatesToLoginService(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $base = $this->createMock(Base::class);
        $base->expects($this->exactly(3))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls('user@example.com', 'new-password', '123456');
        $base->expects($this->once())
            ->method('sucJson')
            ->with('密码修改成功！', [], 0)
            ->willReturn($response);

        $loginService = $this->createMock(LoginService::class);
        $loginService->expects($this->once())
            ->method('systemResetPassword')
            ->with('user@example.com', 'new-password', '123456');

        $controller = new AccountController(
            $this->createMock(AccountService::class),
            $loginService,
            $base,
            $this->createRequest(['email' => 'user@example.com', 'password' => 'new-password', 'captcha' => '123456']),
            $this->createMock(ValidatorFactoryInterface::class),
        );

        $this->assertSame($response, $controller->systemResetPassword());
    }

    public function testEnterOperateRejectsBaseMethodName(): void
    {
        $controller = new EnterController(
            $this->createMock(AccountService::class),
            $this->createMock(PermissionsService::class),
            $this->createMock(AccountController::class),
            $this->createMock(MenuController::class),
            $this->createMock(RoleController::class),
            $this->createMock(PermissionsController::class),
            $this->createMock(Base::class),
            $this->createRequest(['action' => 'success']),
            $this->createMock(ValidatorFactoryInterface::class),
        );

        $this->expectException(CommonException::class);
        $this->expectExceptionMessage('System Not Find Action');

        $controller->operate();
    }

    private function createRequest(array $queryParams = [], ?array $parsedBody = null): ServerRequestInterface
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn($queryParams);
        $request->method('getParsedBody')->willReturn($parsedBody);

        return $request;
    }
}
