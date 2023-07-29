<?php
declare (strict_types = 1);

namespace isszz\captcha\support;

class Str
{
	/**
	 * 取字符串长度
	 *
	 * @param string $string
	 * @return string
	 */
	public static function strlen($string)
	{
		return mb_strlen($string, 'UTF-8');
	}

    /**
     * 截取字符串
     *
     * @param  string   $string
     * @param  int      $start
     * @param  int|null $length
     * @return string
     */
    public static function substr(string $string, int $start, int $length = null): string
    {
        return mb_substr($string, $start, $length, 'UTF-8');
	}
	
	/**
	 * 取字符unicode编码
	 *
	 * @param string $string
	 * @return string
	 */
	public static  function unicode($string)
	{
		preg_match_all('/./u', $string, $matches);
	 
		$unicodeStr = '';
		foreach($matches[0] as $m) {
			$unicodeStr .= base_convert(bin2hex(iconv('UTF-8', 'UCS-4', $m)), 16, 10);
		}

		return $unicodeStr;
	}

	/**
	 * Generate a random alpha or alpha-numeric string.
	 *
	 * <code>
	 *		// Generate a 40 character random alpha-numeric string
	 *		echo Str::random(40);
	 *
	 *		// Generate a 16 character random alphabetic string
	 *		echo Str::random(16, 'alpha');
	 * <code>
	 *
	 * @param  int	   $length
	 * @param  string  $type
	 * @return string
	 */
	public static function random($length, $type = 'alnum')
	{
		return substr(str_shuffle(str_repeat(static::pool($type), 5)), 0, $length);
	}

	/**
	 * Get the character pool for a given type of random string.
	 *
	 * @param  string  $type
	 * @return string
	 */
	protected static function pool($type)
	{
		switch ($type)
		{
			case 'alpha':
				return 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			case 'alnum':
				return '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			default:
				throw new \isszz\captcha\CaptchaException("Invalid random string type [$type].");
		}
	}
}