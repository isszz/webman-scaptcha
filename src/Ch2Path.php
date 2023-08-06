<?php
declare (strict_types = 1);

namespace isszz\captcha;

use isszz\captcha\font\ {
    Font, Glyph
};
use isszz\captcha\support\ {
    Str, Arr, Cache
};

class Ch2Path
{
    public $font = null;
    public $glyph;
    public $glyphs = [];
    public $glyphMaps = [];

    public $ascent;
    public $descent;

    public $ascender;
    public $descender;
    public $unitsPerEm;
    
    public $fontName;
    public $cache;

    public function __construct(string $font = '')
    {
        /*if(empty($font)) {
            throw new CaptchaException('The font file name cannot be empty');
        }*/

        $this->fontName = $font ?: __DIR__ . '/fonts/Comismsh.ttf';
        $this->cache = Cache::make($this->fontName);
        // $this->getGlyph($font);
    }

    /**
     * 生成文字svg path
     * 
     * @param  string  $text
     * @param  array  $options
     * @return object
     */
    public function get($text, $options)
    {
        $data = null;

        // 开启缓存字形
        if (!empty($options['cache'])) {
            unset($options['cache']);
            // 取字形缓存
            $data = $this->cache->get($text .'|'. ($options['size'] ?? 72)) ?: null;
        }

        if (is_null($data)) {
            $this->getGlyph($this->fontName);

            if(empty($this->font)) {
                throw new CaptchaException('Please load the font first.');
            }

            $fontSize = $options['size'];
            $unitsPerEm = $this->unitsPerEm;
            $ascender = $this->ascender;
            $descender = $this->descender;
            $fontScale = bcdiv("{$fontSize}", "{$unitsPerEm}", 18);

            $this->glyph = new Glyph($unitsPerEm/*, $this->fontName*/);

            $glyphWidth = $this->charToGlyphPath($text);

        } else {
            $fontSize = $data['size'];
            $unitsPerEm = $data['unitsPerEm'];
            $fontScale = bcdiv("{$fontSize}", "{$unitsPerEm}", 18);
            $ascender = $data['ascender'];
            $descender = $data['descender'];

            $glyphWidth = $data['glyphWidth'];

            $commands = $data['commands'] ?? [];
            $this->glyph = new Glyph($unitsPerEm/*, $this->fontName*/, $commands);

            // get points cache 
            $points = $this->cache->get($text .'|'. $fontSize, 'points');
            $this->glyph->buildPath($points);
        }

        $width = $glyphWidth * $fontScale;
        $left = $options['x'] - $width / 2;
        $height = ($ascender + $descender) * $fontScale;
        $top = $options['y'] + $height / 2;

        $path = $this->glyph->getPath($left, $top - 4, $fontSize);

        if (is_null($data)) {
            // 写入缓存
            $this->cache->put($text, [
                'text' => $text,
                'size' => $fontSize,
                'scale' => $fontScale,
                'ascender' => $ascender,
                'descender' => $descender,
                'glyphWidth' => $glyphWidth,
                'unitsPerEm' => $unitsPerEm,
                'commands' => $path->commands,
            ]);
        }

        foreach($path->commands as $key => $cmd) {
            $path->commands[$key] =$this->rndPathCmd($cmd);
        }

        return $path->PathData();
    }

    /**
     * 获取文字的glyph
     * 
     * @param  string  $text
     * @return object
     */
    public function charToGlyphPath($text)
    {
        $glyphIndex = $this->charToGlyphIndex($text);

        $glyph = Arr::get($this->glyphs, $glyphIndex);

        if(empty($glyph)) {
            throw new CaptchaException('Glyph does not exist.');
        }
        
        try {
            $glyph->parseData();
        } catch (\Exception $e) {
            throw new CaptchaException('Error parsing glyph containing unsupported characters or corrupted fonts.');
        }

        $glyphWidth  = (abs($glyph->xMin) + $glyph->xMax);

        // add points cache
        $this->cache->put($text, $glyph->points, 'points');

        // build path
        $this->glyph->buildPath($glyph->points);

        return $glyphWidth;
    }

    /**
     * 获取文字的glyph索引id
     * 
     * @param  string  $text
     * @return void
     */
    public function charToGlyphIndex($text)
    {
        $code = Str::unicode($text);

        if ($this->glyphMaps) {
            foreach($this->glyphMaps as $unicode => $glyphIndex) {
                if($unicode == $code) {
                    return $glyphIndex;
                }
            }
        }

        return null;
    }

    /**
     * 获取需要的字形数据
     * 
     * @param  string  $font
     */
    public function getGlyph(string $font = '')
    {
        if (!is_null($this->font)) {
            return false;
        }

        $this->font = $this->font ?? Font::load($font);
        $this->font->parse();

        $this->glyphMaps = $this->font->getUnicodeCharMap();

        $this->glyphs = $this->font->getData('glyf');

        $head = $this->font->getData('head');
        $hhea = $this->font->getData('hhea');

        $this->ascender = $hhea['ascent'];
        $this->descender = $hhea['descent'];
        $this->unitsPerEm = $head['unitsPerEm'];
    }

    public function rndPathCmd($cmd)
    {
        $r = (Random::random() * 0.8) - 0.1;
    
        switch ($cmd['type']) {
            case 'M':
            case 'L':
                $cmd['x'] += $r;
                $cmd['y'] += $r;
                break;
            case 'Q':
            case 'C':
                $cmd['x'] += $r;
                $cmd['y'] += $r;
                $cmd['x1'] += $r;
                $cmd['y1'] += $r;
                break;
            default:
                // Close path cmd
                break;
        }
        return $cmd;
    }
}
