<?php
declare (strict_types = 1);

namespace isszz\captcha\store;

use isszz\captcha\Store;
use isszz\captcha\support\Str;

class SessionStore extends Store
{
	/**
	 * Get token
	 *
	 * @param string $token
	 * @return string
	 */
	public function get(string $token): array
	{
		$session = \request()->session();

		if(!$session->has(self::TOKEN_PRE . $token)) {
			return [];
		}

		$payload = $session->get(self::TOKEN_PRE . $token);

		if(empty($payload)) {
			return [];
		}

		$payload = $this->encrypter->decrypt($payload);

		if(empty($payload)) {
			return [];
		}

		($payload['d'] ?? false) && $session->delete(self::TOKEN_PRE . $token);

		return json_decode($payload, true);
	}

	/**
	 * Storage token
	 *
	 * @param string|int $text
	 * @param string|int $disposable
	 * @return string
	 */
	public function put(string|int $text, string|int $disposable): string
	{
		[$token, $payload] = $this->buildPayload($text, $disposable);

		\request()->session()->put(self::TOKEN_PRE . $token, $payload, $this->ttl);

		return $token;
	}
	
    public function forget(string $token): bool
    {
		$session = \request()->session();

		if(!$session->has(self::TOKEN_PRE . $token)) {
			return false;
		}
		
		$session->forget(self::TOKEN_PRE . $token);

		return true;
    }
}
