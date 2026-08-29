<?php
declare (strict_types = 1);

namespace isszz\captcha\support;

class Cache
{
    public string $fontName;
    
	public function __construct(string $fontName)
	{
        $fontName = basename($fontName);
        $this->fontName = mb_substr($fontName, 0, strrpos(basename($fontName), '.'));
	}

    public static function make(string $fontName = '')
    {
    	return new static($fontName);
    }

    /**
     * 获取字形缓存
     * 
     * @param  string|int  $text
     * @return array|string|null
     */
    public function get(string|int $text, $type = 'glyf')
    {
        $file = $this->getPath($text, $type);

        if (is_file($file)) {
            $content = File::read($file);
            return $content ? json_decode($content, true) : null;
		}
		
        return null;
    }

    /**
     * 写入字形缓存
     * 
     * @param  string|int  $text
     * @param  ?array  $data
     * @return string
     */
    public function put(string|int $text, ?array $data = null, string $type = 'glyf')
    {
        $file = $this->getPath($text, $type);
        $content = json_encode($data, JSON_UNESCAPED_UNICODE);
        return File::write($file, $content, 'rb+', true, true, true) !== false;
    }

    public function getPath(string|int $text, string $type = 'glyf')
    {
        $path = \runtime_path('scaptcha'). DIRECTORY_SEPARATOR .'glyph'. DIRECTORY_SEPARATOR . $this->fontName . DIRECTORY_SEPARATOR;

        $path .= ($type === 'base' ? '' : $type . DIRECTORY_SEPARATOR) . md5($text) .'.json';

        return $path;
    }

    /**
     * Recursively delete a directory.
     *
     * @param  string  $directory
     * @return void
     */
    public static function delete($directory = null)
    {
        if ($directory === null) {
            $directory = \runtime_path('scaptcha'. DIRECTORY_SEPARATOR .'glyph');
        }

        if (!is_dir($directory)) return;

        $items = new \FilesystemIterator($directory);

        foreach ($items as $item) {
            if ($item->isDir()) {
                static::delete($item->getRealPath());
            } else {
                @unlink($item->getRealPath());
            }
        }

        unset($items);
    }
}
