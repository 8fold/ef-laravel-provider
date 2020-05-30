<?php

namespace Eightfold\Site\Shoop;

use Spatie\YamlFrontMatter\YamlFrontMatter;

use Eightfold\Shoop\Helpers\Type;
use Eightfold\Shoop\Shoop;
use Eightfold\Shoop\ESString;

use Eightfold\Markup\UIKit;

class ESMarkdown
{
    private $value = "";

    static public function fold($args)
    {
        return new static($args);
    }

    public function __construct($content)
    {
        $this->value = Type::sanitizeType($content, ESString::class);
    }

    public function string()
    {
        return Shoop::string($this->value());
    }

    public function value()
    {
        return $this->value;
    }

    public function unfold()
    {
        return $this->value();
    }

    public function parsed()
    {
        return YamlFrontMatter::parse($this->value());
    }

    public function content($markdownReplacements = [], $caseSensitive = true)
    {
        return Shoop::string($this->parsed()->body())->replace($markdownReplacements, $caseSensitive);
    }

    public function meta()
    {
        return Shoop::object($this->parsed()->matter());
    }

    public function html($markdownReplacements = [], $htmlReplacements = [], $caseSensitive = true, $minified = true)
    {
        $content = $this->content($markdownReplacements, $caseSensitive);
        $html = UIKit::markdown($content)->unfold();
        $html = Shoop::string($html)->replace($htmlReplacements, $caseSensitive);
        if ($minified) {
            $html = $html->replace(["\t" => "", "\n" => "", "\r" => "", "\r\n" => ""]);
        }
        return Shoop::string($html);
    }

    public function isEmpty(\Closure $closure = null)
    {
        $bool = Type::isEmpty($this);
        $value = $this->value();
        if ($closure === null) {
            $closure = function($bool, $value) {
                return Shoop::this($bool);
            };
        }
        return $closure($bool, Shoop::this($value));
    }
}
