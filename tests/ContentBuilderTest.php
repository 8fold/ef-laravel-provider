<?php

namespace Eightfold\Site\Tests;

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

    public function testTitle()
    {
        $expected = "Root";
        $actual = ContentBuilder::title();
        $this->assertSame($expected, $actual->unfold());

        $actual = ContentBuilder::title(ContentBuilder::BOOKEND);
        $this->assertSame($expected, $actual->unfold());

        $this->visit("/somewhere/else");
        $expected = "Else | Somewhere | Root";
        $actual = ContentBuilder::title();
        $this->assertSame($expected, $actual->unfold());

        $expected = "Else";
        $actual = ContentBuilder::title(ContentBuilder::TITLE);
        $this->assertSame($expected, $actual->unfold());
    }

    public function testPageTitle()
    {
        $this->visit("/somewhere/else");
        $expected = "Else | Somewhere | Root";
        $actual = ContentBuilder::title();
        $this->assertSame($expected, $actual->unfold());

        $expected = "Else | Root";
        $actual = ContentBuilder::title(ContentBuilder::BOOKEND);
        $this->assertSame($expected, $actual->unfold());

        $this->visit("/events/2020/05");
        $expected = 'May 2020 | Root';
        $actual = ContentBuilder::title(ContentBuilder::BOOKEND);
        $this->assertSame($expected, $actual->unfold());
    }

    public function testContentDetails()
    {
        $this->visit("/somewhere/else");
        $expected = Shoop::dictionary([
            'created' => 'Apr 1, 2020',
            'modified' => 'Jun 3, 2020',
            'moved' => 'May 1, 2020',
            'original' => 'https://8fold.pro 8fold'
        ]);
        $actual = ContentBuilder::contentDetails();
        $this->assertSame($expected->unfold(), $actual->unfold());

        $expected = '<p>Created on Apr 1, 2020 (updated Jun 3, 2020), which was<br> originally posted on <a href="https://8fold.pro">8fold</a> and moved May 1, 2020.</p>';
        $actual = ContentBuilder::contentDetailsView();
        $this->assertSame($expected, $actual->unfold());
    }

    public function testShare()
    {
        $this->visit("/");
        $expected = '<meta content="website" property="og:type"><meta content="Root" property="og:title"><meta content="http://localhost" property="og:url"><meta content="Root" property="og:description"><meta content="https://8fold.pr/media/og/default-image.png" property="og:image"><meta name="twitter:card" content="summary_large_image">';
        $actual = ContentBuilder::shareMeta();
        $this->assertEquals($expected, $actual->unfold());
    }

    public function testBreadcrumbs()
    {
        $this->visit("/somewhere/else");
        $expected = '<nav class="breadcrumbs"><ul><li><a href="/somewhere">Somewhere</a></li></ul></nav>';
        $actual = ContentBuilder::breadcrumbs();
        $this->assertSame($expected, $actual->unfold());

        $expected = '<nav class="breadcrumbs"><ul><li><a href="/somewhere">Somewhere</a></li><li><a href="/">Home</a></li></ul></nav>';
        $actual = ContentBuilder::breadcrumbs("Home");
        $this->assertSame($expected, $actual->unfold());

        $expected = '<nav class="breadcrumbs"><ul><li><a href="/somewhere/else">Else</a></li><li><a href="/somewhere">Somewhere</a></li></ul></nav>';
        $actual = ContentBuilder::breadcrumbs("", true);
        $this->assertSame($expected, $actual->unfold());
    }

    public function testPaginationPages()
    {
        // $this->visit("/feed/page/1")->seePageIs("/feed");
    }

    public function testUriContentMarkdownToc()
    {
        // $this->visit("/toc");
        // $expected = ["/", "/somewhere", "/somewhere/else"];
        // $actual = ContentBuilder::toc(1);
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
