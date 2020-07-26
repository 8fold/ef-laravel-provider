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

    // private function remoteBuilder()
    // {
    //     return new TestContentBuilder(
    //         Shoop::path(__DIR__)->plus("content"),
    //         Shoop::path("tests")->plus("content")
    //     );
    // }

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

    public function testMeta()
    {
        $this->visit("/");
        $expected = '<meta name="viewport" content="width=device-width,initial-scale=1"><link type="image/x-icon" rel="icon" href="/assets/favicons/favicon.ico"><link rel="apple-touch-icon" href="/assets/favicons/apple-touch-icon.png" sizes="180x180"><link rel="image/png" href="/assets/favicons/favicon-32x32.png" sizes="32x32"><link rel="image/png" href="/assets/favicons/favicon-16x16.png" sizes="16x16"><meta content="website" property="og:type"><meta content="Root" property="og:title"><meta content="http://localhost" property="og:url"><meta content="Root" property="og:description"><meta content="http://localhost/media/poster.jpg" property="og:image"><meta name="twitter:card" content="summary_large_image"><link rel="stylesheet" href="/css/main.css"><script src="/js/main.js"></script>';
        $actual = $this->localBuilder()->meta();
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

    public function testToc()
    {
        $this->visit("/toc");

        $items = $this->localBuilder()->handler()->contentStore()->metaMember("toc");
        if ($items === null) {
            $items = [];
        }

        $expected = '<ul><li><a href="/">Root</a></li><li><a href="/somewhere">Somewhere</a></li><li><a href="/somewhere/else">Else heading</a></li></ul>';
        $actual = $this->localBuilder()->toc(1, $items)->each(function($ui) { return $ui->unfold(); })->join("");
        $this->assertEquals($expected, $actual->unfold());
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
