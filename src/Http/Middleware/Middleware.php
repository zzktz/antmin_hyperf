<?php

declare(strict_types=1);

namespace Antmin\Http\Middleware;

use Antmin\Common\Limit;
use Antmin\Exceptions\CommonException;
use Antmin\Http\Service\AccountService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Middleware implements MiddlewareInterface
{
    private const IP_REQUEST_LIMIT = 200;
    private const IP_REQUEST_WINDOW = 60;

    public function __construct(
        private readonly AccountService $accountService,
        private readonly Limit $limit,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = trim($request->getUri()->getPath(), '/');
        $method = $this->getMethodName($path);
        if (in_array($method, Filter::getFilterMethod(), true)) {
            return $handler->handle($request->withAttribute('request_start_time', microtime(true)));
        }

        $token = $request->getHeaderLine('Access-Token');
        $server = $request->getServerParams();
        $ip = $server['remote_addr'] ?? 'unknown';
        $limitKey = 'middleware_ip_' . md5($ip);
        if (! $this->limit->handle($limitKey, self::IP_REQUEST_LIMIT, self::IP_REQUEST_WINDOW)) {
            throw new CommonException('您的请求太快了，超过了最大允许量！');
        }
        if ($token === '') {
            throw new CommonException('Access-Token 不存在', [], -1, 401);
        }

        $accountId = $this->accountService->getAccountIdByToken($token);
        return $handler->handle(
            $request
                ->withAttribute('request_start_time', microtime(true))
                ->withAttribute('accountId', $accountId)
        );
    }

    private function getMethodName(string $path): string
    {
        $parts = explode('/', $path);
        $result = end($parts) ?: '';
        if ($result === '') {
            throw new CommonException('请求路径非法');
        }
        return $result;
    }
}
