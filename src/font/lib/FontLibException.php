<?php

namespace isszz\captcha\font\lib;

class FontLibException extends \Exception
{
    public function __construct($message = '')
    {
        parent::__construct($message);
    }
}