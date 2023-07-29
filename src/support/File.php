<?php
declare (strict_types = 1);

namespace isszz\captcha\support;


class File
{
	/**
	 * Create a new directory.
	 *
	 * @param  string  $path
	 * @param  int     $chmod
	 * @return void
	 */
	public static function mkdir($path, $chmod = 0777)
	{
		return (!is_dir($path)) ? mkdir($path, $chmod, true) : true;
	}

	/**
	 * fwrite 写文件
	 *
	 * @param string $fileName 文件绝对路径
	 * @param string $data 数据
	 * @param string $method 读写模式,默认模式为rb+
	 * @param bool $ifLock 是否锁文件，默认为true即加锁
	 * @param bool $ifCheckPath 是否检查文件名中的“..”，默认为true即检查
	 * @param bool $ifChmod 是否将文件属性改为可读写,默认为true
	 * @return int 返回写入的字节数
	 */
	public static function write($fileName, $data, $method = 'rb+', $ifLock = true, $ifCheckPath = true, $ifChmod = true)
	{
		$dirname = dirname($fileName);

		if (!file_exists($dirname) && !is_dir($dirname) && !self::mkdir($dirname)) {
			throw new  \Exception('Create dir is error.');
		}

		touch($fileName);

		if (!$handle = fopen($fileName, $method)) {
			return false;
		}

		$ifLock && flock($handle, LOCK_EX);
		$writeCheck = fwrite($handle, $data);
		$method == 'rb+' && ftruncate($handle, strlen($data));
		fclose($handle);
		$ifChmod && chmod($fileName, 0777);

		return $writeCheck;
	}

	/**
	 * 读取文件
	 *
	 * @param string $fileName 文件绝对路径
	 * @param string $method 读取模式默认模式为rb
	 * @return string 从文件中读取的数据
	 */
	public static function read($fileName, $method = 'rb')
	{
		if (!is_file($fileName)) {
			return '';
		}

		$data = '';
		if (!$handle = fopen($fileName, $method)) {
			return false;
		}

		while (!feof($handle)) {
			$data .= fgets($handle, 4096);
		}

		fclose($handle);

		return $data;
	}

	/**
	 * 将变量的值转换为字符串
	 *
	 * @param mixed $input   变量
	 * @param string $indent 缩进, 默认为''
	 * @return string
	 */
	public static function var2String($input, $indent = '', $isValue = false)
	{
		switch (gettype($input)) {
			case 'string':
				return "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], $input) . "'";
			case 'array':
				$output = "[\r\n";
				foreach ($input as $key => $value) {
					$output .= $indent . "\t" . self::var2String($key, $indent . "\t", false) . ' => ' . self::var2String(
						$value, $indent . "\t", true);
					$output .= ",\r\n";
				}
				$output .= $indent . ']';
				return $output;
			case 'boolean':
				return $input ? 'true' : 'false';
			case 'NULL':
				return 'null';
			case 'integer':
			case 'double':
			case 'float':
				// return "'" . (string) $input . "'";
				return $isValue == false ? "'" . (string) $input . "'" : $input;
		}

		return 'null';
	}

	/**
	 * 保存文件
	 *
	 * @param string $fileName          保存的文件名
	 * @param mixed $data               保存的数据
	 * @param boolean $isBuildReturn    是否组装保存的数据是return $params的格式，如果没有则以变量声明的方式保存,默认为true则以return的方式保存
	 * @param string $method            打开文件方式，默认为rb+的形式
	 * @param boolean $ifLock           是否对文件加锁，默认为true即加锁
	 */
	public static function savePhpData($fileName, $data, $isBuildReturn = true, $method = 'rb+', $ifLock = true)
	{
		$temp = "<?php\r\n ";
		if (!$isBuildReturn && is_array($data)) {
			foreach ($data as $key => $value) {
				if (!preg_match('/^\w+$/', $key)) continue;
				$temp .= "\$" . $key . " = " . self::var2String($value) . ";\r\n";
			}
			$temp .= "\r\n?>";
		} else {
			($isBuildReturn) && $temp .= " return ";
			$temp .= self::var2String($data) . ";\r\n";
		}

		return self::write($fileName, $temp, $method, $ifLock);
	}
}