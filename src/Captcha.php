<?php
declare (strict_types = 1);

namespace isszz\captcha;

use isszz\captcha\support\Str;
use isszz\captcha\support\encrypter\Encrypter;

/**
 * SVG 验证码
 */
class Captcha
{
    /**
     * Default config
     */
    protected $config = [
        'type' => null,
        'cache' => true,
        'api' => false, // 是否是API模式
        // 设置为true时不管验证对错, 都会删除存储凭证，若验证失败则需要刷新一次验证码
        // 设置为false时, 直到验证输入正确时, 才删除存储凭证，也就是允许试错
        'disposable' => false,
        'width' => 150,
        'height' => 50,
        'noise' => 5, // 干扰线条的数量
        'inverse' => false, // 反转颜色
        'color' => false, // 文字是否随机色
        'background' => 'fefefe', // 验证码背景色
        'size' => 4, // 验证码字数
        'ignoreChars' => '', // 验证码字符中排除
        'fontSize' => 72, // 字体大小
        'char' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', // 预设随机字符
        'math' => '', // 计算类型, 如果设置不是+或-则随机两种
        'mathMin' => 1, // 用于计算的最小值
        'mathMax' => 9, // 用于计算的最大值
        'salt' => '^%$YU$%%^U#$5', // 用于加密验证码的盐
        'font' => '', // 用于验证码的字体, 字体文件需要放置根目录config/fonts/目录下面

        // api模式，token机制
        'token' => [
            // 也可以自定义\app\common\libs\MyStore::class,
            // 自带可选：redis，session；建议使用redis
            'store' => 'redis',
            'expire' => 300,
            'redis' => [
                'host'       => '127.0.0.1',
                'port'       => 6379,
                'password'   => '',
                'select'     => 0,
                'timeout'    => 0,
            ],
        ],
    ];

    /**
     * think session
     */
    protected ?object $session;

    /**
     * Encrypter object
     */
    protected ?object $encrypter = null;

    /**
     * Font random
     */
    protected ?object $random;

    /**
     * Get font path
     */
    protected ?object $ch2path;

    /**
     * Store drive
     */
    private ?object $store = null;

    /**
     * To svg string
     */
    private ?string $text;

    /**
     * Encode hash
     */
    private ?string $hash;

    /**
     * store token
     */
    private ?string $token;

    /**
     * To svg string
     */
    public ?string $svg;

    public ?float $mctime;


    /**
     * 初始化
     *
     * @param ?object $session
     * @return self
     */
    public function __construct()
    {
        // $this->session = \session();

        if (\config('app.debug')) {
            $this->mctime();
        }

        return $this;
    }

    /**
     * 创建文字验证码
     *
     * @param array $config
     * @param bool $api
     * @return self
     */
    public function create(array $config = [], bool $api = false): self
    {
        if ($api) {
            $config['api'] = true;
        }

        if(!empty($config['math'])) {
            return $this->createMath($config, $api);
        }

        $this->config = $this->config($config);

        $this->initFont($this->config['font']);

        $text = $this->random->captchaText($this->config);

        $this->svg = $this->generate($text);

        return $this;
    }

    /**
     * 创建计算类型验证码
     *
     * @param array $config
     * @param bool $api
     * @return self
     */
    public function createMath(array $config = [], bool $api = false): self
    {
        if ($api) {
            $config['api'] = true;
        }

        $this->config = $this->config($config);

        $this->initFont($this->config['font']);

        [$answer, $equation] = $this->random->mathExpr($this->config['mathMin'], $this->config['mathMax'], $this->config['math']);
        
        $this->svg = $this->generate($equation, $answer);

        return $this;
    }

    /**
     * 生成验证码
     *
     * @param string $text
     * @return string
     */
    protected function generate(string $text, ?string $answer = null): string
    {
        $text = $text ?: $this->random->captchaText();

        $this->setHash($answer ?: $text);

        $width = $this->config['width'];
        $height = $this->config['height'];

        $rect = '<rect width="100%" height="100%" fill="#' . ($this->config['background'] ?: 'fefefe') . '"/>';
        $paths = array_merge($this->getLineNoise($width, $height), $this->getText($text, $width, $height,));

        shuffle($paths);

        $paths = implode('', $paths);

        $start = '<svg xmlns="http://www.w3.org/2000/svg" width="'. $width .'" height="'. $height .'" viewBox="0,0,'. $width .','. $height .'" author="CFYun">';

        return $start . $rect . $paths . '</svg>';
    }

    /**
     * 生成并写入hash的session
     *
     * @param string $text
     * @return void
     */
    private function setHash(string $text): void
    {
        $text = mb_strtolower($text, 'UTF-8');

        // api模式, 需要使用token机制时
        if ($this->config['api'] && !empty($this->config['token']['store'])) {
            $this->token = $this->store()->put($text, $this->config['disposable']);
        }

        if ($this->config['disposable'] == 2) {
            $text .= '!';
        }

        \session()->put('scaptcha', $this->encrypter()->encrypt($text));
    }

    /**
     * 验证验证码是否正确
     *
     * @param string $code 验证码
     * 
     * @throws \isszz\captcha\CaptchaException
     * @return bool 验证码是否正确
     */
    public function check(string $code, string|null $token = null): bool
    {
        $this->config = $this->config();

        // 携带token则已api模式验证
        if ($token && !empty($this->config['token']['store'])) {
            $payload = $this->store()->get($token);

            if(empty($payload)) {
                return false;
            }

            if(!isset($payload['ttl']) || time() > $payload['ttl']) {
                throw new CaptchaException('Captcha timeout.');
            }

            if(!isset($payload['ip']) || request()->ip() !== $payload['ip']) {
                throw new CaptchaException('The IP address has been changed. The verification failed.');
            }

            if(!isset($payload['ua']) || crc32(request()->header('User-Agent')) !== $payload['ua']) {
                throw new CaptchaException('The device has been switched, and the verification fails.');
            }

            if(!isset($payload['text'])) {
                throw new CaptchaException('Captcha error.');
            }

            if($code == $payload['text']) {
                return true;
            }

            throw new CaptchaException('Captcha Validation failed.');

            return false;
        }

        // 普通session模式
        if (!\session()->has('scaptcha')) {
            return false;
        }

        $hash = \session()->get('scaptcha');

        $text = is_null($hash) ? null : $this->encrypter()->decrypt($hash);

        // 检测URL里设置一次性验证码
        if ($text && str_contains($text, '!')) {
            $this->config['disposable'] = 2;
            $text = rtrim($text, '!');
        }

        $res = $code === $text;

        if ($res || ($this->config['disposable'] && $this->config['disposable'] == 2)) {
            \session()->delete('scaptcha');
        }

        return $res;
    }

    /**
     * 生成干扰线条
     *
     * @param int $width
     * @param int $height
     * @return array
     */
    private function getLineNoise ($width, $height): array
    {
        $width = (int) $width;
        $height = (int) $height;

        $min = isset($this->config['inverse']) ? 7 : 1;
        $max = isset($this->config['inverse']) ? 15 : 9;

        $i = -1;
        $noiseLines = [];
        while (++$i < $this->config['noise']) {
            $start = Random::randomInt(1, 21) . ' ' . Random::randomInt(1, $height - 1);
            $end = Random::randomInt($width - 21, $width - 1) . ' ' . Random::randomInt(1, $height - 1);
            $mid1 = Random::randomInt(($width / 2) - 21, ($width / 2) + 21) . ' ' . Random::randomInt(1, $height - 1);
            $mid2 = Random::randomInt(($width / 2) - 21, ($width / 2) + 21) . ' ' . Random::randomInt(1, $height - 1);

            $color = $this->config['color'] ? $this->random->color() : $this->random->greyColor($min, $max);

            $noiseLines[] = '<path d="M' . $start . ' C' . $mid1 . ',' . $mid2 . ',' . $end . '" stroke="' . $color . '" fill="none"/>';
        }

        return $noiseLines;
    }

    /**
     * 获取文字svg path
     *
     * @param string $text
     * @param int $width
     * @param int $height
     * @return array
     */
    private function getText(string $text, $width, $height): array
    {
        $width = (int) $width;
        $height = (int) $height;

        $len = Str::strlen($text);

        $spacing = ($width - 2) / ($len + 1);
        $min = $max = 0;

        if(!empty($this->config['inverse'])) {
            $min = 10;
            $max = 14;
        }

        $i = -1;

        // 中文, 不建议使用
        if(preg_match ("/[\x{4e00}-\x{9fa5}]/u", $text)) {
            $text = preg_split('/(?<!^)(?!$)/u', $text);
        } else {
            $text = str_split($text);
        }

        $out = [];
        $opts = [
            'size' => $this->config['fontSize'],
            'cache' => $this->config['cache'],
        ];

        $opts['x'] = $spacing * (0 + 1);
        $opts['y'] = $height / 2;

        while (++$i < $len) {
            $opts['x'] = $spacing * ($i + 1);
            $opts['y'] = $height / 2;

            $charPath = $this->ch2path->get($text[$i], $opts);

            $color = $this->config['color'] ? $this->random->color() : $this->random->greyColor($min, $max);
            $out[] = '<path fill="' . $color . '" d="' . $charPath . '"/>';
        }

        return $out;
    }

    /**
     * 载入字体初始化相关
     *
     * @param string|null $font
     */
    private function initFont(?string $font = null)
    {
        $this->random = $this->random ?? new Random;
        $this->ch2path = $this->ch2path ?? new Ch2Path($font);
    }

    public function getToken()
    {
        return $this->token ?? null;
    }

    /**
     * Initialize storage drive
     *
     * @return object
     */
    private function store(): object|null
    {
        if(!is_null($this->store)) {
            return $this->store;
        }

        $config = $this->config['token'];

        $class = match($config['store']) {
            // 可以跨域名
            'redis' => \isszz\captcha\store\RedisStore::class,
            'cache' => \isszz\captcha\store\CacheStore::class,
            'session' => \isszz\captcha\store\SessionStore::class,
            // 可能是自定义的
            default => $config['store'],
        };

        $class = $class ?? \isszz\captcha\store\SessionStore::class;

        if (!is_string($class) || !class_exists($class) || !is_subclass_of($class, Store::class)) {
            throw new CaptchaException('Captcha storage drive class: '. $class .'. invalid.');
        }

        return $this->store = new $class($this, $this->encrypter(), $config['expire']);
    }

    /**
     * Initialize encrypter
     *
     * @return object
     */
    private function encrypter(): object
    {
        if(!is_null($this->encrypter)) {
            return $this->encrypter;
        }

        return $this->encrypter = new Encrypter($this->config['salt']);
    }

    /**
     * 获取配置
     *
     * @param array $config
     * @return array
     */
    public function config($config = []): array
    {
        $defaultConfig = config('plugin.isszz.webman-scaptcha.app', []);

        if (!empty($config['type'])) {
            return array_merge($this->config, $defaultConfig, ($defaultConfig[$config['type']] ?? []), $config);
        }

        return array_merge($this->config, $defaultConfig, (array) $config);
    }

    /**
     * 清空字形缓存
     */ 
    public function deleteCache()
    {
        \isszz\captcha\support\Cache::delete();
    }

    public function mctime($isReturn = false)
    {
        [$msec, $sec] = explode(' ', microtime());
        $mctime = floatval($msec) + floatval($sec);

        if ($isReturn) {
            return round(($mctime - $this->mctime) * 1000, 3) . ' ms';
        }

        $this->mctime = $mctime;
    }

    public function image($type = 1)
    {
        if($this->svg) {
            if ($type == 1) {
                return 'data:image/svg+xml,'. str_replace(
                    ['"', '%', '#', '{', '}', '<', '>'],
                    ["'", '%25', '%23', '%7B', '%7D', '%3C', '%3E'],
                    $this->svg
                );
            }

            return 'data:image/svg+xml;base64,'. chunk_split(base64_encode($this->svg));
        }

        return '';
    }

    /**
     * 获取验证码
     */
    public function __toString()
    {
        return $this->svg ?: '';
    }
}
