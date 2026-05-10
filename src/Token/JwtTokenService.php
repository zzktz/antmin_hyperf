<?php

declare(strict_types=1);

namespace Antmin\Token;

use Antmin\Contract\TokenServiceInterface;
use Antmin\Exceptions\CommonException;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Redis\RedisFactory;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\HasClaimWithValue;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\RelatedTo;

class JwtTokenService implements TokenServiceInterface
{
    private Configuration $configuration;

    public function __construct(
        private readonly ConfigInterface $config,
        private readonly RedisFactory $redisFactory,
    ) {
        $secret = (string) $this->config->get('antmin.token.secret', 'change-me');
        $this->configuration = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($secret));
    }

    public function issueToken(int $accountId): string
    {
        $issuer = (string) $this->config->get('antmin.token.issuer', 'antmin');
        $audience = (string) $this->config->get('antmin.token.audience', 'antmin-admin');
        $expireSeconds = (int) $this->config->get('antmin.token.expire_seconds', 2592000);
        $roleClaim = (string) $this->config->get('antmin.token.role_claim', 'antadmin');
        $now = new \DateTimeImmutable();

        $token = $this->configuration->builder()
            ->issuedBy($issuer)
            ->permittedFor($audience)
            ->relatedTo((string) $accountId)
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($now->modify(sprintf('+%d seconds', $expireSeconds)))
            ->withClaim($roleClaim, $roleClaim)
            ->getToken($this->configuration->signer(), $this->configuration->signingKey())
            ->toString();

        $this->saveTokens($token, $accountId, $expireSeconds);
        return $token;
    }

    public function parseToken(string $token): int
    {
        try {
            $parsed = $this->configuration->parser()->parse($token);
            $issuer = (string) $this->config->get('antmin.token.issuer', 'antmin');
            $audience = (string) $this->config->get('antmin.token.audience', 'antmin-admin');
            $roleClaim = (string) $this->config->get('antmin.token.role_claim', 'antadmin');

            $constraints = [
                new IssuedBy($issuer),
                new PermittedFor($audience),
                new LooseValidAt(new \DateTimeZone(date_default_timezone_get() ?: 'UTC')),
                new HasClaimWithValue($roleClaim, $roleClaim),
            ];

            $this->configuration->validator()->assert($parsed, ...$constraints);
            $accountId = (int) $parsed->claims()->get('sub');
            if ($accountId < 1) {
                throw new CommonException('Token 无效，请重新获取');
            }
            if (! $this->isTokenExists($token, $accountId)) {
                throw new CommonException('超过终端最大许可数，设备下线。');
            }
            return $accountId;
        } catch (CommonException $exception) {
            throw $exception;
        } catch (\Throwable $throwable) {
            throw new CommonException('Token 无效，请重新获取');
        }
    }

    private function saveTokens(string $token, int $accountId, int $expireSeconds): void
    {
        $redis = $this->redisFactory->get('default');
        $key = (string) $this->config->get('antmin.token.redis_prefix', 'account_tokens:') . $accountId;
        $maxTokens = (int) $this->config->get('antmin.token.max_tokens_per_user', 3);
        $milliseconds = (int) floor(microtime(true) * 1000);

        $redis->multi();
        $redis->zAdd($key, $milliseconds, $token);
        $redis->expire($key, $expireSeconds);
        $redis->exec();
        $redis->zRemRangeByRank($key, 0, -($maxTokens + 1));
    }

    private function isTokenExists(string $token, int $accountId): bool
    {
        $redis = $this->redisFactory->get('default');
        $key = (string) $this->config->get('antmin.token.redis_prefix', 'account_tokens:') . $accountId;
        return $redis->zScore($key, $token) !== false;
    }
}
