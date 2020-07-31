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
        parent::getEnvironmentSetUp($app);

        $app->make('Illuminate\Contracts\Http\Kernel')
            ->pushMiddleware('Illuminate\Session\Middleware\StartSession');
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

        $expected = '<ul><li><a href="/toc/toc-child">Next previous check</a></li><li><a href="/toc/toc-child-2">Next previous check 2</a></li></ul>';
        $actual = $this->localBuilder()->toc(1, $items)->each(function($ui) { return $ui->unfold(); })->join("");
        $this->assertEquals($expected, $actual->unfold());

        // TODO: failing
        // $expected = '<!doctype html><html lang="en"><head><title>Root</title><meta name="viewport" content="width=device-width,initial-scale=1"><link type="image/x-icon" rel="icon" href="/assets/favicons/favicon.ico"><link rel="apple-touch-icon" href="/assets/favicons/apple-touch-icon.png" sizes="180x180"><link rel="image/png" href="/assets/favicons/favicon-32x32.png" sizes="32x32"><link rel="image/png" href="/assets/favicons/favicon-16x16.png" sizes="16x16"><meta content="website" property="og:type"><meta content="Root" property="og:title"><meta content="http://localhost/toc" property="og:url"><meta content="Root" property="og:description"><meta content="http://localhost/media/poster.jpg" property="og:image"><meta name="twitter:card" content="summary_large_image"><link rel="stylesheet" href="/css/main.css"><script src="/js/main.js"></script></head><body><ul><li><a href="/">Root</a></li><li><a href="/somewhere">Somewhere</a></li><li><a href="/somewhere/else">Else heading</a></li></ul></body></html>';
        // $actual = $this->localBuilder()->tocView(1, "/toc");
        // $this->assertEquals($expected, $actual->unfold());
    }

    public function testNext()
    {
        $this->visit("/toc");
        $expected = '<a class="next" href="/toc/toc-child">next: Next previous check</a>';
        $actual = $this->localBuilder()->nextAnchor();
        $this->assertEquals($expected, $actual->unfold());

        $this->visit("/toc/toc-child");
        $expected = '<a class="next" href="/toc/toc-child-2">next: Next previous check 2</a>';
        $actual = $this->localBuilder()->nextAnchor();
        $this->assertEquals($expected, $actual->unfold());

        $this->visit("/toc/toc-child-2");
        $expected = '';
        $actual = $this->localBuilder()->nextAnchor();
        $this->assertEquals($expected, $actual->unfold());
    }

    public function testPrevious()
    {
        $this->visit("/toc");
        $expected = '';
        $actual = $this->localBuilder()->previousAnchor();
        $this->assertEquals($expected, $actual->unfold());

        $this->visit("/toc/toc-child");
        $expected = '<a class="previous" href="/toc">previous: Table of contents</a>';
        $actual = $this->localBuilder()->previousAnchor();
        $this->assertEquals($expected, $actual->unfold());

        $this->visit("/toc/toc-child-2");
        $expected = '<a class="previous" href="/toc/toc-child">previous: Next previous check</a>';
        $actual = $this->localBuilder()->previousAnchor();
        $this->assertEquals($expected, $actual->unfold());
    }
}
