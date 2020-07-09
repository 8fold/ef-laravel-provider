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
            static::store()->markdown()->html()->unfold()
        );
    }

    static public function shareImage(): ESString
    {
        return Shoop::string("https://8fold.pr/media/og/default-image.png");
    }

    static public function rootStore(): ESStore
    {
        return Shoop::store(__DIR__)->plus("content");
    }






    // static public function pageUri(): ESString
    // {
    //     return Shoop::string(request()->path())->isEmpty(function($result) {
    //         return ($result)
    //             ? Shoop::string("/")
    //             : Shoop::string(request()->path());
    //     });
    // }

    // static public function contentStorePath(): ESString
    // {
    //     return Shoop::string(__DIR__)->plus("/content");
    // }



















    // static public function uriDir($base = __DIR__): ESString
    // {
    //     return parent::uriDir($base);
    // }

    // static public function copyright($name, $startYear = ""): ESString
    // {
    //     return parent::copyright("Eightfold");
    // }

    // static public function uriContentMarkdownDetails()
    // {
    //     return Shoop::array([])->plus(
    //         UIKit::p(parent::uriContentMarkdownDetails()->join(UIKit::br())->unfold())->unfold(),
    //         UIKit::p("Hello")->unfold()
    //     );
    // }
}
