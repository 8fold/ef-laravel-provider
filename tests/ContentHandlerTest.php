<?php

namespace Eightfold\Site\Tests;

use Orchestra\Testbench\BrowserKit\TestCase;
// use Orchestra\Testbench\TestCase;

use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;

use Eightfold\Site\Tests\TestContentHandler as ContentHandler;

use Eightfold\ShoopExtras\Shoop;

class ContentHandlerTest extends TestCase
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

    private function cacheRoot()
    {
        return Shoop::string(__DIR__)->plus("/content");
    }

    private function rmrf($dir)
    {
        foreach (glob($dir) as $file) {
            if (is_dir($file)) {
                $this->rmrf("$file/*");
                rmdir($file);

            } else {
                unlink($file);

            }
        }
    }

    public function tearDown(): void
    {
        $this->rmrf($this->cacheRoot()->plus("/.cache"));
    }

    public function testLocalPath()
    {
        $expected = Shoop::path(__DIR__)->plus("tests", "data");
        $actual = ContentHandler::fold(
            Shoop::path(__DIR__)->plus("tests", "data")
        )->localRoot();
        $this->assertEquals($expected, $actual->unfold());

        $actual = ContentHandler::fold(
            Shoop::path(__DIR__)->plus("tests", "data")
        )->useLocal();
        $this->assertTrue($actual);

        $actual = ContentHandler::fold(
            Shoop::path(__DIR__)->plus("tests", "data"),
            Shoop::path("tests")->plus("data")
        )->useLocal();
        $this->assertFalse($actual);
    }

    public function testLocalContent()
    {
        // $this->visit("/inner-folder");
        $expected = Shoop::path(__DIR__)
            ->plus("tests", "data", "inner-folder", "content.md");
        $actual = ContentHandler::fold(
            Shoop::path(__DIR__)->plus("tests", "data")
        )->contentStore("inner-folder");
        $this->assertEquals($expected, $actual->unfold());

        $expected = Shoop::path("tests")
            ->plus("data", "inner-folder", "content.md");
        $actual = ContentHandler::fold(
            Shoop::path(__DIR__)->plus("tests", "data"),
            Shoop::path("tests")->plus("data")
        )->contentStore("inner-folder");
        $this->assertEquals($expected->unfold(), $actual->unfold());
    }

    public function testMarkdown()
    {
        // local
        $expected = "Hello, World!";
        $actual = ContentHandler::fold(
            Shoop::path(__DIR__)->plus("content")
        )->contentStore("somewhere", "else")->markdown()->body()->trim();
        $this->assertEquals($expected, $actual->unfold());

        // remote
        $actual = ContentHandler::fold(
            Shoop::path(__DIR__)->plus("content"),
            Shoop::path("tests")->plus("content")
        )->contentStore("somewhere", "else")->markdown()->body()->trim();
        $this->assertEquals($expected, $actual->unfold());
    }

    public function testTitles()
    {
        // local
        // $expected = ["Root"];
        // $actual = ContentHandler::fold(
        //     Shoop::path(__DIR__)->plus("content")
        // )->titles();
        // $this->assertSame($expected, $actual->unfold());

        // $expected = ["Else heading", "Somewhere", "Root"];
        // $actual = ContentHandler::fold(
        //     Shoop::path(__DIR__)->plus("content")
        // )->titles(true, ["somewhere", "else"]);
        // $this->assertSame($expected, $actual->unfold());

        // remote
        $expected = ["Else heading", "Somewhere", "Root"];
        $actual = ContentHandler::fold(
            Shoop::path(__DIR__)->plus("content"),
            Shoop::path("tests")->plus("content")
        )->titles(true, ["somewhere", "else"]);
        $this->assertSame($expected, $actual->unfold());
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

    // public function testUseLocal()
    // {
    //     $actual = ContentHandler::useLocal();
    //     $this->assertTrue($actual);

    //     $actual = ContentHandler::useLocal(false);
    //     $this->assertFalse($actual);
    // }

    // public function testRemoteRoot()
    // {
    //     $expected = "/tests/content";
    //     $actual = ContentHandler::remoteRoot();

    // }
//     public function testUriAndContentStoreSet()
//     {
//         // TODO: Make these methods private until absolutely necessary.
//         // $builder = ContentBuilder::fold();

//         $expected = "/";
//         $actual = ContentBuilder::uri();
//         $this->assertEquals($expected, $actual->unfold());

//         // $expected = __DIR__ ."/content";
//         // $actual = ContentBuilder::store();
//         // $this->assertEquals($expected, $actual->unfold());
//     }

//     public function testCanGetRemoteAssets()
//     {
//         $expected = __DIR__ ."/content/.assets";
//         $actual = ContentBuilder::assetsStore();
//         $this->assertEquals($expected, $actual->unfold());
//     }

//     public function testUri()
//     {
//         // $this->visit("/somewhere");
//         // $expected = "/somewhere";
//         // $actual = ContentBuilder::uri();
//         // $this->assertSame($expected, $actual->unfold());

//         // $this->visit("/somewhere/else");
//         // $expected = ["somewhere", "else"];
//         // $actual = ContentBuilder::uriParts();
//         // $this->assertSame($expected, $actual->unfold());

//         // $expected = "/somewhere";
//         // $actual = ContentBuilder::uriRoot();
//         // $this->assertSame($expected, $actual->unfold());

//         // $this->visit("/");
//         // $expected = "/";
//         // $actual = ContentBuilder::uriRoot();
//         // $this->assertSame($expected, $actual->unfold());
//     }

//     public function testTitle()
//     {
//         $expected = "Root";
//         $actual = ContentBuilder::title();
//         $this->assertSame($expected, $actual->unfold());

//         $actual = ContentBuilder::title(ContentBuilder::BOOKEND);
//         $this->assertSame($expected, $actual->unfold());

//         $this->visit("/somewhere/else");
//         $expected = "Else | Somewhere | Root";
//         $actual = ContentBuilder::title();
//         $this->assertSame($expected, $actual->unfold());

//         $expected = "Else";
//         $actual = ContentBuilder::title(ContentBuilder::TITLE);
//         $this->assertSame($expected, $actual->unfold());
//     }

//     public function testPageTitle()
//     {
//         $this->visit("/somewhere/else");
//         $expected = "Else | Somewhere | Root";
//         $actual = ContentBuilder::title();
//         $this->assertSame($expected, $actual->unfold());

//         $expected = "Else | Root";
//         $actual = ContentBuilder::title(ContentBuilder::BOOKEND);
//         $this->assertSame($expected, $actual->unfold());

//         $this->visit("/events/2020/05");
//         $expected = 'May 2020 | Root';
//         $actual = ContentBuilder::title(ContentBuilder::BOOKEND);
//         $this->assertSame($expected, $actual->unfold());
//     }

//     public function testContentDetails()
//     {
//         $this->visit("/somewhere/else");
//         $expected = Shoop::dictionary([
//             'created' => 'Apr 1, 2020',
//             'modified' => 'Jun 3, 2020',
//             'moved' => 'May 1, 2020',
//             'original' => 'https://8fold.pro 8fold'
//         ]);
//         $actual = ContentBuilder::contentDetails();
//         $this->assertSame($expected->unfold(), $actual->unfold());

//         $expected = '<p>Created on Apr 1, 2020 (updated Jun 3, 2020), which was<br> originally posted on <a href="https://8fold.pro">8fold</a> and moved May 1, 2020.</p>';
//         $actual = ContentBuilder::contentDetailsView();
//         $this->assertSame($expected, $actual->unfold());
//     }

//     public function testShare()
//     {
//         $this->visit("/");
//         $expected = '<meta content="website" property="og:type"><meta content="Root" property="og:title"><meta content="http://localhost" property="og:url"><meta content="Root" property="og:description"><meta content="https://8fold.pr/media/og/default-image.png" property="og:image"><meta name="twitter:card" content="summary_large_image">';
//         $actual = ContentBuilder::shareMeta();
//         $this->assertEquals($expected, $actual->unfold());
//     }

//     public function testBreadcrumbs()
//     {
//         $this->visit("/somewhere/else");
//         $expected = '<nav class="breadcrumbs"><ul><li><a href="/somewhere">Somewhere</a></li></ul></nav>';
//         $actual = ContentBuilder::breadcrumbs();
//         $this->assertSame($expected, $actual->unfold());

//         $expected = '<nav class="breadcrumbs"><ul><li><a href="/somewhere">Somewhere</a></li><li><a href="/">Home</a></li></ul></nav>';
//         $actual = ContentBuilder::breadcrumbs("Home");
//         $this->assertSame($expected, $actual->unfold());

//         $expected = '<nav class="breadcrumbs"><ul><li><a href="/somewhere/else">Else</a></li><li><a href="/somewhere">Somewhere</a></li></ul></nav>';
//         $actual = ContentBuilder::breadcrumbs("", true);
//         $this->assertSame($expected, $actual->unfold());
//     }

//     public function testPaginationPages()
//     {
//         // $this->visit("/feed/page/1")->seePageIs("/feed");
//     }

//     public function testUriContentMarkdownToc()
//     {
//         // $this->visit("/toc");
//         // $expected = ["/", "/somewhere", "/somewhere/else"];
//         // $actual = ContentBuilder::toc(1);
//         // $this->assertEquals($expected, $actual->unfold());
//     }

//     public function testTocObject()
//     {
//         // $this->visit("/toc");
//         // $expected = '';
//         // $actual = ContentBuilder::uriToc();
//         // $this->assertEquals($expected, $actual->unfold());

// /*
//         $this->visit("/toc");
//         $expected = '<nav><ul><li><a href="/">Root</a></li><li><a href="/somewhere">Somewhere</a></li><li><a href="/somewhere/else">Else</a></li></ul></nav>';
//         $actual = ContentBuilder::uriToc()->tocAnchors();
//         $this->assertEquals($expected, $actual->unfold());
//         */
//     }

//     public function testGitHubIntegration()
//     {
//         if (file_exists(__DIR__ ."/.env")) {
//             $token = env("GITHUB_PERSONAL_TOKEN");
//             $githubClient = ContentBuilder::githubClient($token);
//             $actual = Shoop::dictionary($githubClient->me()->show())->login;
//             $this->assertNotNull($actual);

//             $actual = $githubClient->plus("README.md")->markdown();
// die(var_dump($actual));
//             // Shoop::markdown(
//             //     $githubClient
//             //         ->api("repo")
//             //         ->contents()
//             //         ->download("8fold", "laravel-provider", "README.md")
//             // )->string()->startsWith("# 8fold Laravel Provider");
//             $this->assertTrue($actual->unfold());

//             $actual = ContentBuilder::store();
//             die(var_dump($actual));

//         } else {
//             // Create a .env file in the root tests folder with
//             // GITHUB_APIKEY defined with the personal access token
//             // you wish to use.
//             $this->assertFalse(false);
//         }
//     }
}
