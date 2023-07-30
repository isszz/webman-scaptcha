<?php
declare (strict_types = 1);

namespace isszz\captcha\facade;

class Captcha
{
    protected static ?\isszz\captcha\Captcha $_instance = null;

    public static function instance()
    {
        if (!static::$_instance) {
            static::$_instance = new \isszz\captcha\Captcha;
        }

        return static::$_instance;
    }

    public static function __callStatic($name, $arguments)
    {
        return static::instance()->{$name}(... $arguments);
    }
}
