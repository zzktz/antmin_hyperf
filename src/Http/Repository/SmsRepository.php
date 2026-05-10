<?php

declare(strict_types=1);

namespace Antmin\Http\Repository;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Redis\RedisFactory;

class SmsRepository
{
    private const CACHE_OUT_TIME = 600;
    private const CODE_DEV_DEFAULT = '8666';
    private const CODE_SEND_TYPE = ['reg', 'login', 'fixPassword', 'forgetPassword'];

    public function __construct(
        private readonly RedisFactory $redisFactory,
        private readonly ConfigInterface $config,
    ) {
    }

    public function getSendType(): array
    {
        return self::CODE_SEND_TYPE;
    }

    public function sendSmsCode(string $mobile, string $ip): int
    {
        $redis = $this->redisFactory->get('default');
        $code = (string) random_int(1000, 9999);
        $redis->setEx($this->getCacheKey($mobile), self::CACHE_OUT_TIME, $code);
        return 1;
    }

    public function checkSmsCode(string $mobile, string $smsCode, bool $isSingle = true): bool
    {
        if ($this->isDevEnv() && $smsCode === self::CODE_DEV_DEFAULT) {
            return true;
        }

        $redis = $this->redisFactory->get('default');
        $key = $this->getCacheKey($mobile);
        if (! $redis->exists($key)) {
            return false;
        }

        $stored = (string) $redis->get($key);
        if ($stored !== $smsCode) {
            return false;
        }

        if ($isSingle) {
            $redis->del($key);
        }

        return true;
    }

    private function getCacheKey(string $mobile): string
    {
        return 'antmin:sms:' . $mobile;
    }

    private function isDevEnv(): bool
    {
        $env = (string) ($this->config->get('app_env') ?? $this->config->get('app.env', 'dev'));
        return $env === 'dev';
    }
}
