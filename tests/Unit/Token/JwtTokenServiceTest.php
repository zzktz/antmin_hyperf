<?php

declare(strict_types=1);

namespace AntminTest\Unit\Token;

use Antmin\Exceptions\CommonException;
use Antmin\Token\JwtTokenService;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Redis\RedisFactory;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use PHPUnit\Framework\TestCase;

class JwtTokenServiceTest extends TestCase
{
    public function testParseTokenRejectsInvalidSignature(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('get')
            ->willReturnCallback(static fn (string $key, mixed $default = null): mixed => match ($key) {
                'antmin.token.secret' => 'service-secret-1234567890-service',
                'antmin.token.issuer' => 'antmin',
                'antmin.token.audience' => 'antmin-admin',
                'antmin.token.role_claim' => 'antadmin',
                default => $default,
            });

        $redisFactory = $this->createMock(RedisFactory::class);
        $redisFactory->expects($this->never())->method('get');

        $service = new JwtTokenService($config, $redisFactory);

        $tokenConfiguration = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText('different-secret-1234567890-token')
        );
        $now = new \DateTimeImmutable();
        $token = $tokenConfiguration->builder()
            ->issuedBy('antmin')
            ->permittedFor('antmin-admin')
            ->relatedTo('7')
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($now->modify('+1 hour'))
            ->withClaim('antadmin', 'antadmin')
            ->getToken($tokenConfiguration->signer(), $tokenConfiguration->signingKey())
            ->toString();

        $this->expectException(CommonException::class);
        $this->expectExceptionMessage('Token 无效，请重新获取');

        $service->parseToken($token);
    }
}
