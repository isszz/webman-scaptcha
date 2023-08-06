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
	public function get(string $token): array
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

		($payload['d'] ?? false) && Cache::delete(self::TOKEN_PRE . $token);

		return json_decode($payload, true);
	}
	
	/**
	 * Storage token
	 * 
	 * @param string|in $text
	 * @param string|int $disposable
	 * @return string
	 */
	public function put(string|int $text, string|int $disposable): string
	{
		[$token, $payload] = $this->buildPayload($text, $disposable);

		Cache::set(self::TOKEN_PRE . $token, $payload, $this->ttl);

		return $token;
	}

    public function forget(string $token): bool
    {
		if(!Cache::has(self::TOKEN_PRE . $token)) {
			return false;
		}

		Cache::delete(self::TOKEN_PRE . $token);

		return true;
    }
}
