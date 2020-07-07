<?php

namespace Eightfold\Site\Tests;

use Orchestra\Testbench\BrowserKit\TestCase;
// use Orchestra\Testbench\TestCase;

use Eightfold\Site\Helpers\Uri;
use Eightfold\Site\Helpers\ContentStore;

use Eightfold\Site\Tests\TestContentBuilder as ContentBuilder;

use Eightfold\ShoopExtras\Shoop;

class ContentBuilderTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return ['Eightfold\Site\Tests\TestProvider'];
    }

    public function testUriAndContentStoreSet()
    {
        // TODO: Make these methods private until absolutely necessary.
        // $builder = ContentBuilder::fold();

        $expected = "/";
        $actual = ContentBuilder::uri();
        $this->assertEquals($expected, $actual->unfold());

        $expected = __DIR__ ."/content";
        $actual = ContentBuilder::store();
        $this->assertEquals($expected, $actual->unfold());
    }

    public function testCanGetRemoteAssets()
    {
        $expected = __DIR__ ."/content/.assets";
        $actual = ContentBuilder::assetsStore();
        $this->assertEquals($expected, $actual->unfold());
    }

    public function testStore()
    {
        $expected = "Root";
        $actual = ContentBuilder::title();
        $this->assertSame($expected, $actual->unfold());

        $this->visit("/somewhere/else");
        $expected = "Else | Somewhere | Root";
        $actual = ContentBuilder::title();
        $this->assertSame($expected, $actual->unfold());

        $expected = "Else";
        $actual = ContentBuilder::store()->plus("content.md")
            ->markdown()->meta()->title;
        $this->assertSame($expected, $actual);
    }

    public function testPageTitle()
    {
        $this->visit("/somewhere/else");
        $base = __DIR__;
        $expected = "Else | Somewhere | Root";
        $actual = ContentBuilder::title();
        $this->assertSame($expected, $actual->unfold());

        $expected = "Else | Root";
        $actual = ContentBuilder::title(ContentBuilder::BOOKEND);
        $this->assertSame($expected, $actual->unfold());
    }

    // public function testPageContent()
    // {
    //     $this->visit("/somewhere/else")->see("Hello, World!");
    // }

    public function testRss()
    {
        $expected = __DIR__ ."/content/feed/content.md";
        $actual = ContentBuilder::rssStore();
        $this->assertEquals($expected, $actual->unfold());
    }

    // public function testPaginationPages()
    // {
    //     $this->visit("/feed/page/1")->seePageIs("/feed");
    // }

    // public function testUriContentMarkdownToc()
    // {
    //     $this->visit("/toc");
    //     $expected = ["/", "/somewhere", "/somewhere/else"];
    //     $actual = ContentBuilder::uriContentMarkdownToc();
    //     $this->assertEquals($expected, $actual->unfold());
    // }
}
