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
    static public function contentStore($root = ""): ESStore
    {
        $root = Type::sanitizeType($root, ESString::class)->unfold();
        return Shoop::string($root)->isEmpty(function($result, $root) {
            return ($result)
                ? Shoop::store(base_path())
                : Shoop::store($root);
        });
    }

    static public function view(...$content)
    {
        return UIKit::webView(static::pageTitle()->unfold());
    }
}
