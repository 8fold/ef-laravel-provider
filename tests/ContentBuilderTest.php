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
        $builder = ContentBuilder::fold();

        $expected = "/";
        $actual = $builder->pageUri();
        $this->assertEquals($expected, $actual->unfold());

        $expected = __DIR__ ."/content";
        $actual = $builder->store();
        $this->assertEquals($expected, $actual->unfold());
    }

    public function testCanGetRemoteAssets()
    {
        $builder = ContentBuilder::fold();

        $expected = __DIR__ ."/content/.assets";
        $actual = $builder->assets();
        $this->assertEquals($expected, $actual->unfold());
    }

    public function testUri()
    {
        // $this->visit("/somewhere");
        // $expected = "/somewhere";
        // $actual = ContentBuilder::uri();
        // $this->assertSame($expected, $actual->unfold());

        // $this->visit("/somewhere/else");
        // $expected = ["somewhere", "else"];
        // $actual = ContentBuilder::uriParts();
        // $this->assertSame($expected, $actual->unfold());

        // $expected = "/somewhere";
        // $actual = ContentBuilder::uriRoot();
        // $this->assertSame($expected, $actual->unfold());

        // $this->visit("/");
        // $expected = "/";
        // $actual = ContentBuilder::uriRoot();
        // $this->assertSame($expected, $actual->unfold());
    }

    public function testStore()
    {
        $builder = ContentBuilder::fold();

        $expected = "Root";
        $actual = $builder->title();
        $this->assertSame($expected, $actual->unfold());

        $this->visit("/somewhere/else");
        $expected = "Else | Somewhere | Root";
        $actual = ContentBuilder::fold()->title();
        $this->assertSame($expected, $actual->unfold());

        // $expected = "Else";
        // $actual = ContentBuilder::contentStore()->markdown()->meta()->title;
        // $this->assertSame($expected, $actual);
    }

    public function testPageTitle()
    {
        // $this->visit("/somewhere/else");
        // $base = __DIR__;
        // $expected = "Else | Somewhere | Root";
        // $actual = ContentBuilder::uriPageTitle();
        // $this->assertSame($expected, $actual->unfold());

        // $expected = "Else | Root";
        // $actual = ContentBuilder::uriShareTitle();
        // $this->assertSame($expected, $actual->unfold());
    }

    public function testPageContent()
    {
        // $this->visit("/somewhere/else")->see("Hello, World!");
    }

    public function testRss()
    {
        // $expected = __DIR__ ."/content/feed/content.md";
        // $actual = ContentBuilder::rssItemsStore();
        // $this->assertEquals($expected, $actual->unfold());

        // $expected = [
        //     "/somewhere/else",
        //     "/somewhere",
        //     "/",
        //     "/somewhere/else",
        //     "/somewhere",
        //     "/",
        //     "/somewhere/else",
        //     "/somewhere",
        //     "/",
        //     "/fake/else",
        //     "/somewhere",
        //     "/"
        // ];
        // $actual = ContentBuilder::rssItemsStoreItems();
        // $this->assertEquals($expected, $actual->unfold());

        // $expected = "Copyright © Eightfold ". date("Y") .". All rights reserved.";
        // $actual = ContentBuilder::copyright();
        // $this->assertEquals($expected, $actual);
/*
        $expected = '<?xml version="1.0"?>'."\n".'<rss version="2.0"><channel><title>8fold Laravel Service Provider</title><link>https://8fold.dev</link><description>A generic service provider for most 8fold projects.</description><language>en-us</language><copyright>Copyright © Eightfold 2020. All rights reserved.</copyright><item><title>Else</title><link>https://8fold.dev/somewhere/else</link><guid>https://8fold.dev/somewhere/else</guid><description>Hello, World!</description><pubDate>Wed, 01 Apr 2020 12:00:00 -0400</pubDate></item><item><title>Somewhere</title><link>https://8fold.dev/somewhere</link><guid>https://8fold.dev/somewhere</guid><description>External link</description></item><item><title>Else</title><link>https://8fold.dev/somewhere/else</link><guid>https://8fold.dev/somewhere/else</guid><description>Hello, World!</description><pubDate>Wed, 01 Apr 2020 12:00:00 -0400</pubDate></item><item><title>Somewhere</title><link>https://8fold.dev/somewhere</link><guid>https://8fold.dev/somewhere</guid><description>External link</description></item><item><title>Else</title><link>https://8fold.dev/somewhere/else</link><guid>https://8fold.dev/somewhere/else</guid><description>Hello, World!</description><pubDate>Wed, 01 Apr 2020 12:00:00 -0400</pubDate></item><item><title>Somewhere</title><link>https://8fold.dev/somewhere</link><guid>https://8fold.dev/somewhere</guid><description>External link</description></item><item><title>Somewhere</title><link>https://8fold.dev/somewhere</link><guid>https://8fold.dev/somewhere</guid><description>External link</description></item></channel></rss>';
        $actual = ContentBuilder::rssCompiled();
        $this->assertEquals($expected, $actual->unfold());
        */
    }

    public function testContentDetails()
    {
        // $this->visit("/somewhere/else");
        // $expected = Shoop::array([
        //     "<p>Modified on: Apr 1, 2020<br>Created on: Apr 1, 2020</p>",
        //     "<p>Hello</p>"
        // ]);
        // $actual = ContentBuilder::uriContentMarkdownDetails();
        // $this->assertSame($expected->unfold(), $actual->unfold());

        // $expected = '<p>Modified on: Apr 1, 2020<br>Created on: Apr 1, 2020</p>';
        // $actual = ContentBuilder::uriContentMarkdownDetailsParagraph();
        // $this->assertSame($expected, $actual->unfold());
    }

    public function testPaginationPages()
    {
        // $this->visit("/feed/page/1")->seePageIs("/feed");
    }

    public function testUriContentMarkdownToc()
    {
        // $this->visit("/toc");
        // $expected = ["/", "/somewhere", "/somewhere/else"];
        // $actual = ContentBuilder::uriContentMarkdownToc();
        // $this->assertEquals($expected, $actual->unfold());
    }

    public function testTocObject()
    {
        // $this->visit("/toc");
        // $expected = '';
        // $actual = ContentBuilder::uriToc();
        // $this->assertEquals($expected, $actual->unfold());

/*
        $this->visit("/toc");
        $expected = '<nav><ul><li><a href="/">Root</a></li><li><a href="/somewhere">Somewhere</a></li><li><a href="/somewhere/else">Else</a></li></ul></nav>';
        $actual = ContentBuilder::uriToc()->tocAnchors();
        $this->assertEquals($expected, $actual->unfold());
        */
    }
}
