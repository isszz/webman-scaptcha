<?php
namespace isszz\captcha\support;

class Redis
{
    /**
     * Redis instance
     */
    protected $handler = null;

    /**
     * Default configuration
     */
    protected $options = [
        'host'       => '127.0.0.1',
        'port'       => 6379,
        'password'   => '',
        'database'   => 0,
        'timeout'    => 0,
        'persistent' => false,
        'expire'     => 0,
    ];

    protected static object|null $connection = null;

    protected static array $config = [];

    public function __construct($options)
    {
        if (!extension_loaded('redis')) {
            throw new \BadFunctionCallException('Please install the Redis extension!');
        }

        if ($this->handler !== null) {
            return;
        }

        if(empty($options)) {
            $options = \config('redis.default');
        } 

        $this->options = array_merge($this->options, (array) $options);

        if (isset($this->options['select'])) {
            $this->options['database'] = (int) $this->options['select'];
            unset($this->options['select']);
        }

        $this->handler = new \Redis;

        if ($this->options['persistent']) {
            $this->handler->pconnect(
                $this->options['host'],
                (int) $this->options['port'],
                (int) $this->options['timeout'],
                'persistent_id_' . $this->options['database']
            );
        } else {
            $this->handler->connect(
                $this->options['host'],
                (int) $this->options['port'],
                (int) $this->options['timeout']
            );
        }

        if ($this->options['password'] != '') {
            $this->handler->auth($this->options['password']);
        }

        if ($this->options['database'] != 0) {
            $this->handler->select((int) $this->options['database']);
        }
    }

    public static function connection(?array $config = []): self
    {
        if (self::$connection === null) {
            self::$connection = new static($config);
        }

        return self::$connection;
    }

    /**
     * Determine if an item exists in the redis.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key): bool
    {
        return $this->handler->exists($key) ? true : false;
    }

    /**
     * Get an item from the redis.
     *
     * @param  string  $key
     * @return void
     */
    public function get(string $key)
    {
        if (!empty($result = $this->handler->get($key))) {
            return unserialize($result);
        }
    }

    /**
     * Write an item to the redis for a given number of minutes.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  int     $expire
     * @return bool
     */
    public function put(string $key, mixed $value, int $expire = null): bool
    {
        if ($expire === null) {
            $expire = $this->options['expire'];
        }

        if ($expire === null) {
            $this->handler->set($key, $value);
        } else {
            $this->handler->setex($key, $expire, serialize($value));
        }

        return true;
    }

    /**
     * Write an item to the redis that lasts forever.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return bool
     */
    public function forever($key, $value): bool
    {
        $this->handler->set($key, serialize($value));
        return true;
    }

    /**
     * Delete an item from the redis.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key): bool
    {
        $result = $this->handler->del($key);
        return $result > 0;
    }

    /**
     * Clear redis.
     *
     * @return bool
     */
    public function clear(): bool
    {
        $this->handler->flushDB();
        return true;
    }
}