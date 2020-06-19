<?php

namespace Eightfold\Site\Tests;

use Eightfold\Site\ContentBuilder;

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
            static::uriPageTitle()->unfold(),
            static::uriContentStore()->markdown()->html()->unfold()
        );
    }

    static public function uri(): ESString
    {
        return Shoop::string(request()->path())->start("/");
    }

    static public function contentStore(): ESStore
    {
        return Shoop::store(__DIR__)->plus("content");
    }
}
