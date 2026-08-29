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

		$data = json_decode($payload, true);

		if(!is_array($data)) {
			return [];
		}

		if (!empty($data['d'])) {
			$session->delete(self::TOKEN_PRE . $token);
		}

		return $data;
	}

	/**
	 * Storage token
	 *
	 * @param string|int $text
	 * @param string|int $disposable
	 * @return string
	 */
	public function put(string|int $text, string|int|bool $disposable): string
	{
		[$token, $payload] = $this->buildPayload($text, $disposable);

		\request()->session()->set(self::TOKEN_PRE . $token, $payload);

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
