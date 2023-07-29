<?php
declare (strict_types = 1);

namespace isszz\captcha;

use Webman\Bootstrap as WebmanBootstrap;
use Workerman\Worker;


class Bootstrap implements WebmanBootstrap
{
    protected static ?\isszz\captcha\Captcha $_instance = null;

    public static function start($worker)
    {
        if ($worker) {
            static::$_instance = new \isszz\captcha\Captcha;
        }
    }

    public static function __callStatic($name, $arguments)
    {
        return static::instance()->{$name}(... $arguments);
    }
}

