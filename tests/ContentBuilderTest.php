<?php

use Orchestra\Testbench\TestCase;

use Eightfold\Site\Tests\TestContentBuilder as ContentBuilder;

use Eightfold\ShoopExtras\Shoop;

class ContentBuilderTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return ['Eightfold\Site\Provider'];
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
        $base = "http://8fold.dev/somewhere/else";

        $expected = ["somewhere", "else"];
        $actual = ContentBuilder::uriParts($base);
        $this->assertSame($expected, $actual->unfold());

        $expected = "somewhere";
        $actual = ContentBuilder::uriRoot($base);
        $this->assertSame($expected, $actual->unfold());
    }

    public function testStore()
    {
        $base = __DIR__;

        $expected = __DIR__;
        $actual = ContentBuilder::contentStore($base);
        $this->assertSame($expected, $actual->unfold());

        $expected = __DIR__ ."/content";
        $actual = ContentBuilder::contentStore($base)->plus("content");
        $this->assertSame($expected, $actual->unfold());

        $expected = "Root";
        $actual = ContentBuilder::uriContentStore("", $base ."/content")
            ->markdown()->meta()->title;
        $this->assertSame($expected, $actual);

        $expected = "Else";
        $actual = ContentBuilder::uriContentStore(
                "/somewhere/else",
                $base ."/content"
            )->markdown()->meta()->title;
        $this->assertSame($expected, $actual);
    }

    public function testPageTitle()
    {
        $base = __DIR__;
        $expected = "Else | Somewhere | Root";
        $actual = ContentBuilder::uriPageTitle(
            "/somewhere/else",
            $base ."/content"
        );
        $this->assertSame($expected, $actual->unfold());
    }
}
