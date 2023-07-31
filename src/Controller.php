<?php
declare (strict_types = 1);

namespace isszz\captcha;

use support\Request;

class Controller
{
    /**
     * 输出为SVG代码，一般用于接口
     */
    public function index(Captcha $captcha, Request $request, string $path = '')
    {
    	// GET访问check会匹配到这里，所以主动抛出404
    	/*if ($path == 'check') {
    		return not_found();
    	}*/

		$config = [];
		if($path) {
			$config = $this->BuildParam($path);
		}

		if (isset($config['reset'])) {
			$captcha->deleteCache();
		}

		$data = [
			'code' => 0,
			'msg' => 'success',
			'token' => null,
			'svg' => null,
		];

		try {
			$content = (string) $captcha->create($config, true)->base64(isset($config['compress']) ? 2 : 1);

			$data['token'] = $captcha->getToken();
			$data['svg'] = $content;
			unset($content);

        	if (config('app.debug')) {
				$data['mtime'] = $captcha->mctime(true);
			}

		} catch (\Exception $e) {
			$data['code'] = 1;
			$data['msg'] = $e->getMessage() ?: 'Unknown error';
		}

		return json($data);
    }

    /**
     * 输出为可视SVG图片
     */
    public function svg(Captcha $captcha, Request $request, string $path = '')
    {
		$config = [];
		if($path) {
			$config = $this->buildParam($path);
		}

		if (isset($config['reset'])) {
			$captcha->deleteCache();
		}

		$content = (string) $captcha->create($config);

	    $headers = [
	    	'Content-Type' => 'image/svg+xml',
	        'Content-Length' => strlen($content),
	    ];

        if (config('app.debug')) {
			$headers['X-Scaptcha-Mtime'] = $captcha->mctime(true);
		}

		return response($content, 200, $headers);
    }

    /**
     * 验证|输出json
     */
    public function check(Captcha $captcha, Request $request)
    {
    	$code = $request->input('code');
    	$token = $request->input('token');

        $json = [
            'code' => 0,
            'msg' => 'success',
        ];

        if (!$code) {
            $json['code'] = 2;
            $json['msg'] = 'The Captcha code cannot be empty';
        	return json($json);
        }

		try {
	    	if (!$captcha->check($code, $token)) {
	            $json['code'] = 1;
	            $json['msg'] = 'Captcha code error';
	    	}
		} catch (\CaptchaException $e) {
			$json['code'] = 3;
			$json['msg'] = $e->getMessage();
		} catch (\Exception $e) {
			$json['code'] = 4;
			$json['msg'] = $e->getMessage() ?: 'Unknown error';
		}


        return json($json);
    }

	/**
	 * 根据url传入参数组装配置
	 *
	 * /scaptcha/w/200/h/60/s/72/l/5
	*/
	protected function buildParam(string $urlPath = '')
	{
		$params = $this->buildPathToParam($urlPath);

		if(empty($params)) {
			return [];
		}

	    $configMapping = [
	        't' => 'type', // 额外配置类型
	        'w' => 'width', // 验证码宽度
	        'h' => 'height', // 验证码高度
	        's' => 'fontSize', // 文字大小
	        'l' => 'size', // 显示文字数量, 非算数模式有效
	        'n' => 'noise', // 干扰线条数量
	        'c' => 'color', // 文字是否随机色
	        'b' => 'background', // 背景色, fefefe
	        'd' => 'disposable', // 是否一次性验证码
	    ];

		$config = [];
	    foreach ($params as $key => $value) {
	        if (isset($configMapping[$key])) {
	            $config[$configMapping[$key]] = $value;
	        }
	    }

	    // 运算模式，1=加法，2=减法，3=乘法，4=除法，或者随机四种
	    if (!empty($params['m'])) {
	        $mathMapping = [
	            1 => '+',
	            2 => '-',
	            3 => '*',
	            4 => '/',
	        ];

	        $config['math'] = $mathMapping[$params['m']] ?? 'rand';
	    }

	    // 是否一次验证码
	    if (isset($config['disposable'])) {
	    	$config['disposable'] = (int) $config['disposable'];
	    }

	    // api模式输出格式1=svg，2=base64
	    if (!empty($params['cs'])) {
	        $config['compress'] = true;
	    }

	    // 禁用缓存字形，生产模式不建议禁用
	    if (!empty($params['rt'])) {
	        $config['cache'] = false;
	    }

	    // 删除已缓存字形，不建议在生产模式一直加在url参数中，否则字形缓存无效，字体文件超过3MB会比较慢
	    if (!empty($params['reset'])) {
	        $config['reset'] = true;
	    }

		return $config;
	}


	/**
	 * 格式化参数字符串
	 *
	 * @param  string  $string
	 * @return array
	 */
	public function buildPathToParam($string = '')
	{
		if (empty($string)) {
			return [];
		}

		// $string = str_replace(['/scaptcha/svg', '/scaptcha'], '', $string);

		if(strpos($string, '=') !== false) {
			$string = str_replace(['=', '&'], '/', $string);
		}

		$array = explode('/', trim($string, '/'));

		$size = count($array);

		$params = [];

		for ($i = 0; $i < $size; $i = $i + 2) {
			if(isset($array[$i + 1])) {
				$params[$array[$i]] = $array[$i + 1];
			}
		}

		return $params;
	}
}
