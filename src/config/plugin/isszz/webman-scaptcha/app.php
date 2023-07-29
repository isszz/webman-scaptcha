<?php

// SVG 验证码配置

return [
    'enable'  => true,
    
    'type' => null, // 单独的配置项
    'cache' => true, // 是否启用字形缓存
    'api' => false, // 是否是API模式
    // 设置为true时不管验证对错, 都会删除存储凭证，若验证失败则需要刷新一次验证码
    // 设置为false时, 直到验证输入正确时, 才删除存储凭证，也就是允许试错
    'disposable' => false,
    'width' => 150, // 宽度
    'height' => 50, // 高度
    'noise' => 5, // 干扰线条的数量
    'inverse' => false, // 反转颜色
    'color' => true, // 文字是否随机色
    'background' => 'fefefe', // 验证码背景色
    'size' => 4, // 验证码字数
    'ignoreChars' => '', // 验证码字符中排除
    'fontSize' => 52, // 字体大小
    'char' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', // 预设随机字符
    'math' => '', // 计算类型, 支持加减乘除, 如果设置不是`+, -, *, /`则随机四种
    'mathMin' => 1, // 用于计算的最小值
    'mathMax' => 9, // 用于计算的最大值
    'salt' => '^%$YU$%%^U#$5', // 用于加密验证码的盐
    'font' => '', // 用于验证码的字体文件路径, 建议字体文件不超过3MB

    // API模式，使用token机制，使用这里的配置后API会携带一个token，在验证时需要携带token和输入的code进行验证
    'token' => [
        // 也可以自定义\app\common\libs\MyStore::class
        // 自带可选：redis，session；建议使用redis
        'store' => 'cache', 
        'expire' => 300,
        'redis' => [
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => '',
            'select' => 1,
            'timeout' => 0,
        ],
    ],

    // 单独的配置, 会覆盖上面的配置
    'test' => [
        'noise' => 3,
        'color' => false,
        'char' => '0123456789',
        // 'token' => null,
    ],
];