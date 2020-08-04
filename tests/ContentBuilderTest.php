<?php

namespace Eightfold\Site\Tests;

use Orchestra\Testbench\BrowserKit\TestCase;
// use PHPUnit\Framework\TestCase;

use Eightfold\ShoopExtras\Shoop;

use Eightfold\Site\Tests\TestContentBuilder;

class ContentBuilderTest extends TestCase
{
    public function testCanAppendMarkdown()
    {
        $builder = TestContentBuilder::fold(Shoop::store(__DIR__)
                ->plus("content"));
        $expected = '<h1>Hello, World!</h1><p>How are you?</p>';
        $actual = $builder->markdown("How are you?");
        $this->assertSame($expected, $actual->unfold());
    }
}
