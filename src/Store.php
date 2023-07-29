<?php
declare (strict_types = 1);

namespace isszz\captcha;

use isszz\captcha\interface\StoreInterface;
use isszz\captcha\support\Str;
use isszz\captcha\support\encrypter\Encrypter;

abstract class Store implements StoreInterface
{
    /**
     * @var Captcha
     */
    protected $captcha;

    /**
     * @var Encrypter
     */
    protected $encrypter;
    
    /**
     * @var int
     */
    protected $ttl;

	public const TOKEN_PRE = 'scaptcha_';

    public function __construct(Captcha $captcha, Encrypter $encrypter, int $ttl)
    {
        $this->captcha = $captcha;
        $this->encrypter = $encrypter;
        $this->ttl = $ttl;
    }

    public function buildPayload(string|int $text): array
    {
        $ua = request()->header('User-Agent');

        $payload = json_encode([
            'text' => $text,
            'ip' => request()->getRealIp(),
            'ua' => crc32($ua),
            'ttl' => time() + $this->ttl,
        ], JSON_UNESCAPED_UNICODE);

        $token = Str::random(32, 'alnum');

        return [$token, $this->encrypter->encrypt($payload)];
    }

    abstract public function get(string $token, bool $disposable): array;
    abstract public function put(string|int $text): string;
    abstract public function forget(string $token): bool;
}
