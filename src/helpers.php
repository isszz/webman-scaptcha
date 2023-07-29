<?php

use isszz\captcha\facade\Captcha;

if (!function_exists('scaptcha')) {
    /**
     * @param array|string $config
     */
    function scaptcha(array|string $config = []): string
    {
        if(is_string($config) && config('scaptcha.'. $config)) {
            $config = ['type' => $config];
        }

        return (string) Captcha::create($config);
    }
}

if (!function_exists('scaptcha_api')) {
    /**
     * @param array|string $config
     * @param int $type
     * @return array
     */
    function scaptcha_api(array|string $config = [], $type = false): array
    {
        if(is_string($config) && config('scaptcha.'. $config)) {
            $config = ['type' => $config];
        }

        $captcha = Captcha::create($config, true);

        return [$captcha->getToken(), $captcha->base64($type ? 2 : 1)];
    }
}

if (!function_exists('scaptcha_src')) {
    /**
     * @param array $config
     * @return string
     */
    function scaptcha_src(array $config = []): string
    {
        $defaults = [
            't' => null,
            'm' => null,
            'w' => 150,
            'h' => 50,
            's' => 52,
            'l' => 4,
            'n' => 3,
            'c' => 1,
            'b' => 'fefefe',
        ];

        $confs = [];
        foreach ($config as $key => $value) {
            if (isset($defaults[$key])) {
                $confs[] = $key . '/' . $value ?: $defaults[$key];
            }
        }

        $urls = implode('/', $confs);
        $url = str_replace('[/{path:.+}]', '', route('scaptcha.svg'));

        return $url . ($urls ? '/'. $urls : '');
    }
}

if (!function_exists('scaptcha_img')) {
    /**
     * @param array $config
     * @param string $id
     * @return string
     */
    function scaptcha_img(array|string $config = [], string $id = ''): string
    {
        if (is_string($config)) {
            $id = $config;
            $config = [];
        }

        $src = scaptcha_src($config);

        return '<img'. ($id ? ' id="'. $id .'"' : '') .' src="'. $src .'" alt="scaptcha" onclick="this.src=\''. $src .'?\'+Math.random();" />';
    }
}

if (!function_exists('scaptcha_check')) {
    /**
     * @param string|int|float $value
     * @param string|null $token
     * 
     * @throws \isszz\captcha\CaptchaException
     * @return bool
     */
    function scaptcha_check(string|int|float $value, string|null $token = null): bool
    {
        return Captcha::check($value, $token);
    }
}
