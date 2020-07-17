<?php

namespace Eightfold\Site\Tests;

use Eightfold\Site\ContentBuilder;

use Eightfold\Site\Helpers\Uri;

use Eightfold\Markup\UIKit;

use Eightfold\ShoopExtras\{
    Shoop,
    ESStore,
    ESPath
};

use Eightfold\Shoop\Helpers\Type;

use Eightfold\Shoop\{
    ESArray,
    ESString
};

class TestContentBuilder extends ContentBuilder
{
    // static public function view(...$content)
    // {
    //     return UIKit::webView(
    //         static::title()->unfold(),
    //         static::store()->markdown()->html()->unfold()
    //     );
    // }

    // static public function markdown($uri = "")
    // {
    //     return Shoop::string($uri)->divide("/", false)->countIsGreaterThan(0,
    //         function($result, $parts) {
    //             $store = static::store();
    //             if ($result->unfold()) {
    //                 $store = static::store(...$parts);
    //             }
    //             return $store->plus("content.md")->extensions(
    //                 ...static::markdownExtensions()
    //             );
    //     });
    // }

    public function socialImage(): ESString
    {
        return Shoop::string("https://8fold.pro/media/og/default-image.png");
    }

    // static public function rootStore(): ESStore
    // {
    //     return Shoop::store(__DIR__)->plus("content");
    // }

    // static public function remoteRoot(): ESPath
    // {
    //     return Shoop::path("tests")->plus("content");
    // }
}
