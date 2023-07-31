<?php
declare (strict_types = 1);

namespace isszz\captcha\interface;

interface StoreInterface
{
    public function get(string $token): array;
    public function put(string|int $text, string|int $disposable): string;
    public function forget(string $token): bool;
}
