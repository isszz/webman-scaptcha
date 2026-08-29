<?php

namespace isszz\captcha;

class CaptchaException extends \Exception
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? '');
    }
}