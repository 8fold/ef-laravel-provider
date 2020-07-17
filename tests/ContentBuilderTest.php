<?php

namespace Eightfold\Site\Tests;

use Orchestra\Testbench\BrowserKit\TestCase;
// use Orchestra\Testbench\TestCase;

use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;

use Eightfold\Site\Tests\TestContentBuilder as ContentBuilder;

use Eightfold\ShoopExtras\Shoop;

class ContentBuilderTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return ['Eightfold\Site\Tests\TestProvider'];
    }

    protected function getEnvironmentSetUp($app)
    {
        if (file_exists(__DIR__ ."/.env")) {
            // make sure, our .env file is loaded
            $app->useEnvironmentPath(__DIR__);
            $app->bootstrapWith([LoadEnvironmentVariables::class]);
            parent::getEnvironmentSetUp($app);
        }
    }

    private function localBuilder()
    {
        return new TestContentBuilder(
            Shoop::path(__DIR__)->plus("content"),
            // Shoop::path("tests")->plus("content")
        );
    }

    private function remoteBuilder()
    {
        return new TestContentBuilder(
            Shoop::path(__DIR__)->plus("content"),
            Shoop::path("tests")->plus("content")
        );
    }

    public function testCanReachURL()
    {
        $this->visit("/")->see("Root");
    }

    public function testRoutes()
    {
        // any
        $this->visit("/somewhere")->see("Somewhere | Root");

        // feed
        // events

        // favicons

        // media


        // ui
    }

    public function testSocial()
    {
        $this->visit("/");
        $expected = '<meta content="website" property="og:type"><meta content="Root" property="og:title"><meta content="http://localhost" property="og:url"><meta content="Root" property="og:description"><meta content="https://8fold.pro/media/og/default-image.png" property="og:image"><meta name="twitter:card" content="summary_large_image">';
        $actual = $this->localBuilder()->socialMeta();
        $this->assertEquals($expected, $actual->unfold());
    }

    public function testBreadcrumbs()
    {
        $this->visit("/somewhere/else");
        $expected = '<nav class="breadcrumbs"><ul><li><a href="/somewhere">Somewhere</a></li></ul></nav>';
        $actual = $this->localBuilder()->breadcrumbs();
        $this->assertSame($expected, $actual->unfold());

        $expected = '<nav class="breadcrumbs"><ul><li><a href="/somewhere">Somewhere</a></li><li><a href="/">Home</a></li></ul></nav>';
        $actual = $this->localBuilder()->breadcrumbs("Home");
        $this->assertSame($expected, $actual->unfold());

        $expected = '<nav class="breadcrumbs"><ul><li><a href="/somewhere/else">Else heading</a></li><li><a href="/somewhere">Somewhere</a></li></ul></nav>';
        $actual = $this->localBuilder()->breadcrumbs("", true);
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
