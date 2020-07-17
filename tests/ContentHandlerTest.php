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

    private function localHandler()
    {
        return ContentHandler::fold(
            Shoop::path(__DIR__)->plus("content")
        );
    }

    private function remoteHandler()
    {
        return ContentHandler::fold(
            Shoop::path(__DIR__)->plus("content"),
            Shoop::path("tests")->plus("content")
        );
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

    public function testUri()
    {
        $expected = "/";
        $actual = ContentHandler::uri();
        $this->assertEquals($expected, $actual->unfold());

        $expected = "/";
        $actual = ContentHandler::uri();
        $this->assertEquals($expected, $actual->unfold());
    }

    public function testLocalPath()
    {
        // local
        $expected = Shoop::path(__DIR__)->plus("content");
        $actual = $this->localHandler()->localRoot();
        $this->assertEquals($expected, $actual->unfold());

        $actual = $this->localHandler()->useLocal();
        $this->assertTrue($actual);

        $expectedAssets = $expected->plus(".assets");
        $actual = $this->localHandler()->assetsStore();
        $this->assertEquals($expectedAssets->unfold(), $actual->unfold());

        $expectedMedia = $expected->plus(".media");
        $actual = $this->localHandler()->mediaStore();
        $this->assertEquals($expectedMedia->unfold(), $actual->unfold());

        $expectedEvents = $expected->plus("events");
        $actual = $this->localHandler()->eventStore();
        $this->assertEquals($expectedEvents->unfold(), $actual->unfold());

        // remote
        $actual = $this->remoteHandler()->useLocal();
        $this->assertFalse($actual);
    }

    public function testLocalContent()
    {
        // $this->visit("/inner-folder");
        $expected = Shoop::path(__DIR__)
            ->plus("content", "inner-folder", "content.md");
        $actual = $this->localHandler()->contentStore(false, "inner-folder");
        $this->assertEquals($expected->unfold(), $actual->unfold());

        $expected = Shoop::path("tests")
            ->plus("content", "inner-folder", "content.md");
        $actual = $this->remoteHandler()->contentStore(false, "inner-folder");
        $this->assertEquals($expected->unfold(), $actual->unfold());
    }

    public function testMarkdown()
    {
        // local
        $expected = "Hello, World!";
        $actual = $this->localHandler()
            ->contentStore(false, "somewhere", "else")
            ->markdown()->body()->trim();
        $this->assertEquals($expected, $actual->unfold());

        // remote
        $actual = $this->remoteHandler()
            ->contentStore(false, "somewhere", "else")
            ->markdown()->body()->trim();
        $this->assertEquals($expected, $actual->unfold());
    }

    public function testTitle()
    {
        $expected = "Root";
        $actual = $this->localHandler()->title();
        $this->assertSame($expected, $actual->unfold());

        $actual = $this->localHandler()->title(ContentHandler::BOOKEND);
        $this->assertSame($expected, $actual->unfold());

        // $this->visit("/somewhere/else");
        $expected = "Else heading | Somewhere | Root";
        $actual = $this->localHandler()->title("", true, ["somewhere", "else"]);
        $this->assertSame($expected, $actual->unfold());

        $expected = "Else";
        $actual = $this->localHandler()
            ->title(ContentHandler::TITLE, true, ["somewhere", "else"]);
        $this->assertSame($expected, $actual->unfold());
    }

    public function testTitles()
    {
        // local
        $expected = ["Root"];
        $actual = $this->localHandler()->titles();
        $this->assertSame($expected, $actual->unfold());

        $expected = ["Else heading", "Somewhere", "Root"];
        $actual = $this->localHandler()->titles(true, ["somewhere", "else"]);
        $this->assertSame($expected, $actual->unfold());

        // remote
        $expected = ["Else heading", "Somewhere", "Root"];
        $actual = $this->remoteHandler()->titles(true, ["somewhere", "else"]);
        $this->assertSame($expected, $actual->unfold());
    }

    public function testContentDetails()
    {
        // local
        $this->visit("/somewhere/else");
        $expected = Shoop::dictionary([
            'created' => 'Apr 1, 2020',
            'modified' => 'Jun 3, 2020',
            'moved' => 'May 1, 2020',
            'original' => 'https://8fold.pro 8fold'
        ]);
        $actual = $this->localHandler()->contentDetails();
        $this->assertSame($expected->unfold(), $actual->unfold());

        $actual = $this->remoteHandler()->contentDetails();
        $this->assertSame($expected->unfold(), $actual->unfold());
    }
}
