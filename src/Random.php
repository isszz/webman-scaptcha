<?php
declare (strict_types = 1);

namespace isszz\captcha;

use isszz\captcha\support\Str;

class Random
{
    /**
     * 获取随机字符
     *
     * @param array $options
     * @return string
     */
    public function captchaText ($options): string
    {
        if (is_numeric($options)) {
            $options = ['size' => (int) $options];
        }

        if (!is_array($options)) {
            $options = [];
        }

        $size = $options['size'] ?? 4;
        $ignoreChars = $options['ignoreChars'] ?? '';

        $i = -1;
        $out = '';
        
        $chars = $options['char'] ?? 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    
        if ($ignoreChars) {
            $chars = $this->stripCharsFromString($chars, $ignoreChars);
        }
    
        $len = Str::strlen($chars) - 1;
        if ($len < 0) {
            return '';
        }
    
        while (++$i < $size) {
            $out .= $chars[self::randomInt(0, $len)];
        }
    
        return $out;
    }

    /**
     * 排除某些字符
     *
     * @param string $string
     * @param string $chars
     * @return string
     */
    public function stripCharsFromString (string $string, string $chars = ''): string
    {
        if ($chars === '' || $string === '') {
            return $string;
        }

        return preg_replace('/[' . preg_quote($chars, '/') . ']/iu', '', $string) ?? $string;
    }

    /**
     * 加法
     *
     * @param int $leftNumber
     * @param int $rightNumber
     * @return array
     */
    public function mathExprPlus(int $leftNumber, int $rightNumber): array
    {
        $answer = $leftNumber + $rightNumber;
        $equation = $leftNumber . '+' . $rightNumber . '=';

        return [(string) $answer, $equation];
    }

    /**
     * 减法
     *
     * @param int $leftNumber
     * @param int $rightNumber
     * @return array
     */
    public function mathExprMinus(int $leftNumber, int $rightNumber): array
    {
        $answer = $leftNumber - $rightNumber;
        $equation = $leftNumber . '-' . $rightNumber . '=';

        return [(string) $answer, $equation];
    }

    /**
     * 乘法
     *
     * @param int $leftNumber
     * @param int $rightNumber
     * @return array
     */
    public function mathExprMul(int $leftNumber, int $rightNumber): array
    {
        $answer = $leftNumber * $rightNumber;
        $equation = $leftNumber . '*' . $rightNumber . '=';

        return [(string) $answer, $equation];
    }

    /**
     * 除法
     *
     * @param int $leftNumber
     * @param int $rightNumber
     * @return array
     */
    public function mathExprDiv(int $leftNumber, int $rightNumber): array
    {
        if ($rightNumber === 0) {
            $rightNumber = 1;
        }

        $answer = intdiv($leftNumber, $rightNumber);
        $leftNumber = $answer * $rightNumber;
        $equation = $leftNumber . '/' . $rightNumber . '=';

        return [(string) $answer, $equation];
    } 
    
    /**
     * Creates a simple math expression using either the + or - operator
     * 
     * @param {number} [min] - The min value of the math expression defaults to 1
     * @param {number} [max] - The max value of the math expression defaults to 9
     * @param {string} [operator] - The operator(s) to use
     * @returns {{equation: string, text: string}}
     */
    public function mathExpr (int $min = 1, int $max = 9, string $operator = ''): array
    {
        $min = $min ?: 1;
        $max = $max ?: 9;

        $left = random_int($min, $max);
        $right = random_int($min, $max);

        switch($operator) {
            case '+':
                return $this->mathExprPlus($left, $right);
            case '-':
                return $this->mathExprMinus($left, $right);
            case '*':
                return $this->mathExprMul($left, $right);
            case '/':
                return $this->mathExprDiv($left, $right);
            default:
                $operations = [
                    $this->mathExprPlus($left, $right),
                    $this->mathExprMinus($left, $right),
                    $this->mathExprMul($left, $right),
                    $this->mathExprDiv($left, $right),
                ];

                return $operations[random_int(0, 3)];
        }
    }

    /**
     * 获取灰色
     *
     * @param int $min
     * @param int $max
     * @return string
     */
    public function greyColor (int $min = 0, int $max = 0): string
    {
        $min = $min ?: 1;
        $max = $max ?: 9;

        $int = max(0, min(255, self::randomInt($min, $max)));
        $hex = sprintf('%02x', $int);

        return "#{$hex}{$hex}{$hex}";
    }
    
    /**
     * 解析并标准化颜色代码
     *
     * @param string $color
     * @return string|null 返回 #rrggbb 格式，解析失败返回 null
     */
    public static function parseColor(string $color): ?string
    {
        $hex = ltrim($color, '#');
        if (!preg_match('/^[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $hex)) {
            return null;
        }

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return '#' . strtolower($hex);
    }

    /**
     * 获取随机色
     *
     * https://github.com/jquery/jquery-color/blob/master/jquery.color.js#L432
     * The idea here is generate color in hsl first and convert that to rgb color
     * @param int $leftNumber
     * @param int $rightNumber
     * @return array
     */
    public function color ($bgColor = null )
    {
        // Random 24 colors
        // or based on step
        $hue = self::randomInt(0, 24) / 24;

        $saturation = self::randomInt(60, 80) / 100;

        $bgLightness = $bgColor === null ? 1.0 : $this->getLightness($bgColor);

        if ($bgLightness >= 0.5) {
            $minLightness = (int) round($bgLightness * 100) - 45;
            $maxLightness = (int) round($bgLightness * 100) - 25;
        } else {
            $minLightness = (int) round($bgLightness * 100) + 25;
            $maxLightness = (int) round($bgLightness * 100) + 45;
        }

        $lightness = self::randomInt($minLightness, $maxLightness) / 100;

        $q = $lightness < 0.5 ? $lightness * ($lightness + $saturation) : $lightness + $saturation - ($lightness * $saturation);

        $p = (2 * $lightness) - $q;

        $r = floor($this->hue2rgb($p, $q, $hue + (1 / 3)) * 255);
        $g = floor($this->hue2rgb($p, $q, $hue) * 255);
        $b = floor($this->hue2rgb($p, $q, $hue - (1 / 3)) * 255);
        /* eslint-disable no-mixed-operators */

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    public function getLightness($rgbColor)
    {
        if (!is_string($rgbColor) || !str_starts_with($rgbColor, '#')) {
            return 1.0;
        }

        $hex = substr($rgbColor, 1);
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return 1.0;
        }

        $hexColor = hexdec($hex);

        $r = $hexColor >> 16;
        $g = $hexColor >> 8 & 255;
        $b = $hexColor & 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);

        return ($max + $min) / (2 * 255);
    }

    public function hue2rgb($p, $q, $h)
    {
        $h = fmod(floatval($h + 1), 1);
        if ($h * 6 < 1) {
            return $p + ($q - $p) * $h * 6;
        }
        if ($h * 2 < 1) {
            return $q;
        }
        if ($h * 3 < 2) {
            return $p + ($q - $p) * ((2 / 3) - $h) * 6;
        }
        return $p;
    }
    
    public static function randomInt(int $min = 0, int $max = 0): int
    {
        $min = $min ?: 0;
        $max = $max ?: 0;

        if ($min > $max) {
            [$min, $max] = [$max, $min];
        }

        if ($min === $max) {
            return $min;
        }

        return random_int($min, $max);
    }

    public static function random(int $min = 0, int $max = 1): float
    {
        if ($min > $max) {
            [$min, $max] = [$max, $min];
        }

        if ($min === $max) {
            return (float) $min;
        }

        return $min + (random_int(0, PHP_INT_MAX) / PHP_INT_MAX) * ($max - $min);
    }
}
