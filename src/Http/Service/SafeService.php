<?php

declare(strict_types=1);

namespace Antmin\Http\Service;

use Antmin\Exceptions\CommonException;
use Hyperf\Redis\RedisFactory;
use Psr\Http\Message\ServerRequestInterface;

class SafeService
{
    private const LOCK_TIME = 30;
    private const ATTEMPT_LIMIT = 5;

    public function __construct(
        private readonly RedisFactory $redisFactory,
        private readonly ServerRequestInterface $request,
    ) {
    }

    public function checking(): void
    {
        $redis = $this->redisFactory->get('default');
        $key = $this->getKey();
        $hasMax = (int) $redis->zCard($key) + 1;
        $last = $redis->zRevRange($key, 0, 0);
        if (empty($last[0])) {
            return;
        }

        $time = (int) ((int) $last[0] / 1000);
        $minute = (int) (($time - time()) / 60) + 1;
        if ($hasMax >= self::ATTEMPT_LIMIT) {
            throw new CommonException('超过' . $hasMax . '次密码错误，请' . $minute . '分钟后重试');
        }
    }

    public function flagFail(): int
    {
        $redis = $this->redisFactory->get('default');
        $key = $this->getKey();
        $milliseconds = (int) floor(microtime(true) * 1000);
        $resultSecond = $milliseconds + (self::LOCK_TIME * 60 * 1000);
        $redis->zAdd($key, $milliseconds, (string) $resultSecond);
        $redis->expire($key, 60 * self::LOCK_TIME);
        return (int) $redis->zCard($key);
    }

    public function flagSuccess(): void
    {
        $this->redisFactory->get('default')->del($this->getKey());
    }

    public function getMaxTip(): string
    {
        return '最大尝试' . self::ATTEMPT_LIMIT . '次后将锁定' . self::LOCK_TIME . '分钟';
    }

    private function getKey(): string
    {
        $userAgent = $this->request->getHeaderLine('user-agent');
        return 'account_safe:' . md5($userAgent);
    }
}
