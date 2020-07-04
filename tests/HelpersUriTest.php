<?php

namespace Eightfold\Site\Tests;

use Orchestra\Testbench\BrowserKit\TestCase;

use Eightfold\Site\Helpers\Uri;

use Eightfold\ShoopExtras\Shoop;

class HelpersUriTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return ['Eightfold\Site\Tests\TestProvider'];
    }

    public function testUri()
    {
        $this->visit("/somewhere/else");
        $this->assertEquals("somewhere/else", request()->path());

        $this->visit("/somewhere/else");
        $expected = "/somewhere/else";
        $actual = Uri::fold("/somewhere/else");
        $this->assertEquals($expected, $actual->unfold());

        $this->visit("/somewhere/else");
        $expected = "/somewhere/else";
        $actual = Uri::fold("/somewhere/else");
        $this->assertEquals($expected, $actual->unfold());
    }

    public function testRoot()
    {
        $expected = "/somewhere";
        $actual = Uri::fold("/somewhere/else")->root();
        $this->assertEquals($expected, $actual->unfold());
    }

    public function testParts()
    {
        $expected = ["somewhere", "else"];
        $actual = Uri::fold("/somewhere/else")->parts();
        $this->assertEquals($expected, $actual->unfold());
    }

    public function testStore()
    {
        $expected = __DIR__ ."/content";
        $actual = Uri::fold("", __DIR__)->store();
        $this->assertEquals($expected, $actual->unfold());
    }

    public function testContent()
    {
        $expected = __DIR__ ."/content/somewhere/else/content.md";
        $actual = Uri::fold("/somewhere/else", __DIR__)->content();
        $this->assertEquals($expected, $actual->unfold());
    }

    public function testMarkdown()
    {
        $expected = 'Hello, World!';
        $actual = Uri::fold("/somewhere/else", __DIR__)->markdown();
        $this->assertEquals($expected, $actual->unfold());
    }

    public function testMeta()
    {
        $expected = new \stdClass();
        $expected->title = "Else";
        $expected->created = 20200401;
        $expected->modified = 20200401;
        $actual = Uri::fold("/somewhere/else", __DIR__)->meta();
        $this->assertEquals($expected, $actual->unfold());
    }

    public function testAssets()
    {
        $expected = __DIR__ ."/content/.assets";
        $actual = Uri::fold("", __DIR__)->assets();
        $this->assertEquals($expected, $actual->unfold());
    }

    public function testMedia()
    {
        $expected = __DIR__ ."/content/.media";
        $actual = Uri::fold("", __DIR__)->media();
        $this->assertEquals($expected, $actual->unfold());
    }
}
