<?php

declare(strict_types=1);

namespace Antmin\Common;

use Hyperf\Redis\RedisFactory;

class Limit
{
    public function __construct(private readonly RedisFactory $redisFactory)
    {
    }

    public function handle(string $key, int $max, int $keepSecond): bool
    {
        $redis = $this->redisFactory->get('default');
        $redisKey = 'Limit:' . $key;
        $current = (int) $redis->incr($redisKey);
        if ($current === 1) {
            $redis->expire($redisKey, $keepSecond);
        }

        return $current <= $max;
    }
}
