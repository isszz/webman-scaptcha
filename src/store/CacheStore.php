<?php
declare (strict_types = 1);

namespace isszz\captcha\store;

use support\Cache;
use isszz\captcha\Store;

class CacheStore extends Store
{
	/**
	 * Get token
	 * 
	 * @param string $token
	 * @return string
	 */
	public function get(string $token, bool $disposable): array
	{
		if(!Cache::has(self::TOKEN_PRE . $token)) {
			return [];
		}

		$payload = Cache::get(self::TOKEN_PRE . $token);

		if(empty($payload)) {
			return [];
		}

		$payload = $this->encrypter->decrypt($payload);

		if(empty($payload)) {
			return [];
		}

		$disposable && Cache::delete(self::TOKEN_PRE . $token);

		return json_decode($payload, true);
	}
	
	/**
	 * Storage token
	 * 
	 * @param string|in $text
	 * @return string
	 */
	public function put(string|int $text): string
	{
		[$token, $payload] = $this->buildPayload($text);

		Cache::set(self::TOKEN_PRE . $token, $payload, $this->ttl);

		return $token;
	}

    public function forget(string $token): bool
    {
		if(!Cache::has(self::TOKEN_PRE . $token)) {
			return true;
		}

		Cache::delete(self::TOKEN_PRE . $token);

		return fakse;
    }
}
