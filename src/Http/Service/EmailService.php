<?php

declare(strict_types=1);

namespace Antmin\Http\Service;

use Antmin\Exceptions\CommonException;
use Hyperf\Redis\RedisFactory;

class EmailService
{
    private const CACHE_OUT_TIME = 900;
    private const ONE_DAY_MAX_NUM = 10;

    public function __construct(private readonly RedisFactory $redisFactory)
    {
    }

    public function sendCode(string $email): bool
    {
        $redis = $this->redisFactory->get('default');
        $key = md5($email);
        $code = (string) random_int(100000, 999999);
        $flagMin = $key . '_flag_min';
        if ($redis->get($flagMin)) {
            throw new CommonException('请求太频繁，最大允许每分获取一次验证码');
        }

        $dayKey = $key . '_day_' . date('Ymd');
        $dayCount = (int) $redis->get($dayKey);
        if ($dayCount >= self::ONE_DAY_MAX_NUM) {
            throw new CommonException('今日验证码发送次数已达上限，请明天再试');
        }

        $redis->setEx($key, self::CACHE_OUT_TIME, $code);
        $redis->setEx($flagMin, 60, '1');
        $redis->incr($dayKey);
        $redis->expire($dayKey, 86400);
        return true;
    }

    public function verifyCode(string $email, string $code): bool
    {
        $redis = $this->redisFactory->get('default');
        $key = md5($email);
        if (! $redis->exists($key)) {
            return false;
        }
        return $redis->get($key) === $code;
    }
}
