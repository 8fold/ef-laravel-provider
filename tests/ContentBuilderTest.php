<?php

use Orchestra\Testbench\BrowserKit\TestCase;
// use Orchestra\Testbench\TestCase;

use Eightfold\Site\Tests\TestContentBuilder as ContentBuilder;

use Eightfold\ShoopExtras\Shoop;

class ContentBuilderTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return ['Eightfold\Site\Tests\TestProvider'];
    }

    public function testCanGetRemoteAssets()
    {
        $expected = '<link rel="stylesheet" href="/css/main.css">';
        $actual = ContentBuilder::stylesheets()->first;
        $this->assertEquals($expected, $actual->unfold());

        $expected = '<script src="/js/main.js"></script>';
        $actual = ContentBuilder::javascripts()->first;
        $this->assertEquals($expected, $actual->unfold());

        $expected = 4;
        $actual = ContentBuilder::faviconPack()->count;
        $this->assertEquals($expected, $actual);
    }

    public function testUri()
    {
        $this->visit("/somewhere");
        $expected = "/somewhere";
        $actual = ContentBuilder::uri();
        $this->assertSame($expected, $actual->unfold());

        $this->visit("/somewhere/else");

        $expected = ["somewhere", "else"];
        $actual = ContentBuilder::uriParts();
        $this->assertSame($expected, $actual->unfold());

        $expected = "somewhere";
        $actual = ContentBuilder::uriRoot();
        $this->assertSame($expected, $actual->unfold());
    }

    public function testStore()
    {
        // $base = __DIR__;

        $expected = __DIR__ ."/content";
        $actual = ContentBuilder::contentStore();
        $this->assertSame($expected, $actual->unfold());

        $expected = "Root";
        $actual = ContentBuilder::uriContentStore()->markdown()->meta()->title;
        $this->assertSame($expected, $actual);

        $this->visit("/somewhere/else");
        $expected = "Else";
        $actual = ContentBuilder::uriContentStore()->markdown()->meta()->title;
        $this->assertSame($expected, $actual);
    }

    public function testPageTitle()
    {
        $this->visit("/somewhere/else");
        $base = __DIR__;
        $expected = "Else | Somewhere | Root";
        $actual = ContentBuilder::uriPageTitle();
        $this->assertSame($expected, $actual->unfold());
    }

    public function testPageContent()
    {
        $this->visit("/somewhere/else")->see("Hello, World!");
    }
}
