<?php

namespace Eightfold\Site\Tests;

use Eightfold\Site\ContentBuilder;

use Eightfold\ShoopExtras\Shoop;

class TestContentBuilder extends ContentBuilder
{
    static public function markdownConfig()
    {
        return Shoop::dictionary([parent::markdownConfig()])
            ->plus("allow", "html_input")
            ->unfold();
    }
}
