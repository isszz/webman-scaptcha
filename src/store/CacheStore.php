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

		$data = json_decode($payload, true);

		if(!is_array($data)) {
			return [];
		}

		if (!empty($data['d'])) {
			Cache::delete(self::TOKEN_PRE . $token);
		}

		return $data;
	}
	
	/**
	 * Storage token
	 * 
	 * @param string|in $text
	 * @param string|int $disposable
	 * @return string
	 */
	public function put(string|int $text, string|int|bool $disposable): string
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
