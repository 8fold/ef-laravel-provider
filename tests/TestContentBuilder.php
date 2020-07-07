<?php

namespace Eightfold\Site\Tests;

use Eightfold\Site\ContentBuilder;

use Eightfold\Site\Helpers\Uri;

use Eightfold\Markup\UIKit;

use Eightfold\ShoopExtras\{
    Shoop,
    ESStore
};

use Eightfold\Shoop\Helpers\Type;

use Eightfold\Shoop\{
    ESArray,
    ESString
};

class TestContentBuilder extends ContentBuilder
{
    static public function view(...$content)
    {
        return UIKit::webView(
            static::title()->unfold(),
            static::store("content.md")->markdown()->html()->unfold()
        );
    }

    static public function copyright($name, $startYear = ''): ESString
    {
        return parent::copyright("Eightfold", $startYear);
    }

    static public function rootStore(): ESStore
    {
        return Shoop::store(__DIR__)->plus("content");
    }

    static public function shareImage(): ESString
    {
        return parent::mediaStore()->plus("poster.jpg");
    }





















    static public function uriDir($base = __DIR__): ESString
    {
        return parent::uriDir($base);
    }


    // static public function uriContentMarkdownDetails()
    // {
    //     return Shoop::array([])->plus(
    //         UIKit::p(parent::uriContentMarkdownDetails()->join(UIKit::br())->unfold())->unfold(),
    //         UIKit::p("Hello")->unfold()
    //     );
    // }
}
