<?php
declare (strict_types = 1);

namespace isszz\captcha\store;

use isszz\captcha\Store;
use isszz\captcha\support\Redis;

class RedisStore extends Store
{
	/**
	 * Get token
	 * 
	 * @param string $token
	 * @return string
	 */
	public function get(string $token, bool $disposable): array
	{
        $redis = $this->redis();

		if(!$redis->has(self::TOKEN_PRE . $token)) {
			return [];
		}

		$payload = $redis->get(self::TOKEN_PRE . $token);

		if(empty($payload)) {
			return [];
		}

		$payload = $this->encrypter->decrypt($payload);

		if(empty($payload)) {
			return [];
		}

		$disposable && $redis->forget(self::TOKEN_PRE . $token);

		return json_decode($payload, true);
	}
	
	/**
	 * Storage token
	 * 
	 * @param string|int $text
	 * @return string
	 */
	public function put(string|int $text): string
	{
		[$token, $payload] = $this->buildPayload($text);

		$this->redis()->put(self::TOKEN_PRE . $token, $payload, $this->ttl);

		return $token;
	}

    public function forget(string $token): bool
    {
        $redis = $this->redis();

		if(!$redis->has(self::TOKEN_PRE . $token)) {
			return false;
		}

		$redis->forget(self::TOKEN_PRE . $token);

		return true;
    }

	public function redis(): object
	{
		$config = $this->captcha->config();

		$config = $config['cross']['redis'] ?? [
	        'host'       => '127.0.0.1',
	        'port'       => 6379,
		];

		return Redis::connection($config);
	}
}
