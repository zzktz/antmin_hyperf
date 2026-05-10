<?php

declare(strict_types=1);

namespace AntminTest\Unit\Http\Middleware;

use Antmin\Common\Limit;
use Antmin\Exceptions\CommonException;
use Antmin\Http\Middleware\Middleware;
use Antmin\Http\Service\AccountService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareTest extends TestCase
{
    public function testAllowlistedRouteSkipsTokenValidation(): void
    {
        $requestWithStart = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $request = $this->createRequest('/custom-prefix/systemLogin');
        $request->expects($this->once())
            ->method('withAttribute')
            ->with('request_start_time', $this->isType('float'))
            ->willReturn($requestWithStart);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($requestWithStart)
            ->willReturn($response);

        $accountService = $this->createMock(AccountService::class);
        $accountService->expects($this->never())->method('getAccountIdByToken');

        $limit = $this->createMock(Limit::class);
        $limit->expects($this->never())->method('handle');

        $middleware = new Middleware($accountService, $limit);

        $this->assertSame($response, $middleware->process($request, $handler));
    }

    public function testProtectedRouteWithoutTokenThrowsUnauthorizedException(): void
    {
        $request = $this->createRequest('/custom-prefix/systemIndexOperate', '');

        $limit = $this->createMock(Limit::class);
        $limit->expects($this->once())
            ->method('handle')
            ->with('middleware_ip_' . md5('127.0.0.1'), 200, 60)
            ->willReturn(true);

        $accountService = $this->createMock(AccountService::class);
        $accountService->expects($this->never())->method('getAccountIdByToken');

        $middleware = new Middleware($accountService, $limit);

        try {
            $middleware->process($request, $this->createMock(RequestHandlerInterface::class));
            $this->fail('Expected CommonException to be thrown.');
        } catch (CommonException $exception) {
            $this->assertSame('Access-Token 不存在', $exception->getMessage());
            $this->assertSame(401, $exception->getStatusCode());
        }
    }

    public function testProtectedRouteWithTokenInjectsAccountIdAndContinues(): void
    {
        $requestWithStart = $this->createMock(ServerRequestInterface::class);
        $requestWithAccount = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $request = $this->createRequest('/custom-prefix/systemIndexOperate', 'token-123');
        $request->expects($this->once())
            ->method('withAttribute')
            ->with('request_start_time', $this->isType('float'))
            ->willReturn($requestWithStart);

        $requestWithStart->expects($this->once())
            ->method('withAttribute')
            ->with('accountId', 7)
            ->willReturn($requestWithAccount);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($requestWithAccount)
            ->willReturn($response);

        $limit = $this->createMock(Limit::class);
        $limit->expects($this->once())
            ->method('handle')
            ->with('middleware_ip_' . md5('127.0.0.1'), 200, 60)
            ->willReturn(true);

        $accountService = $this->createMock(AccountService::class);
        $accountService->expects($this->once())
            ->method('getAccountIdByToken')
            ->with('token-123')
            ->willReturn(7);

        $middleware = new Middleware($accountService, $limit);

        $this->assertSame($response, $middleware->process($request, $handler));
    }

    private function createRequest(string $path, string $token = ''): ServerRequestInterface
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn($path);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('getHeaderLine')->with('Access-Token')->willReturn($token);
        $request->method('getServerParams')->willReturn(['remote_addr' => '127.0.0.1']);

        return $request;
    }
}
