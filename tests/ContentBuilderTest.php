<?php

namespace Eightfold\Site\Tests;

use Orchestra\Testbench\BrowserKit\TestCase;
// use PHPUnit\Framework\TestCase;

use Eightfold\ShoopShelf\Shoop;

use Eightfold\Site\Tests\TestContentBuilder;

class ContentBuilderTest extends TestCase
{
    private function builder()
    {
        return TestContentBuilder::fold(
            Shoop::store(__DIR__)->append(["content"])
        );
    }

    public function testCanAppendMarkdown()
    {
        $expected = '<h1>Hello, World!</h1><p>How are you?</p>';
        $actual = $this->builder()->markdown("How are you?");
        $this->assertSame($expected, $actual->unfold());
    }

    public function testCanGetUri()
    {
        // $expected = "/";
        // $actual = $this->builder()->handler()->uri();
        // $this->assertSame($expected, $actual->unfold());

        // $this->visit("/sub");
        // $expected = "/sub";
        // $actual = $this->builder()->handler()->uri();
        // $this->assertSame($expected, $actual->unfold());
    }
}
