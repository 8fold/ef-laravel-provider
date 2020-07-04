<?php

namespace Eightfold\Site\Tests;

use Orchestra\Testbench\BrowserKit\TestCase;

use Eightfold\Site\Helpers\ContentStore;

use Eightfold\ShoopExtras\Shoop;

class HelpersContentStoreTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return ['Eightfold\Site\Tests\TestProvider'];
    }

    public function testStore()
    {
        $expected = __DIR__ ."/content";
        $actual = ContentStore::store();
        $this->assertEquals($expected, $actual->unfold());
    }

    public function testContent()
    {
        $expected = __DIR__ ."/content/somewhere/else/content.md";
        $actual = ContentStore::uri("somewhere/else")->content();
        $this->assertEquals($expected, $actual->unfold());
    }

    public function testAssets()
    {
        $expected = __DIR__ ."/content/.assets";
        $actual = ContentStore::assets();
        $this->assertEquals($expected, $actual->unfold());
    }

    public function testMedia()
    {
        $expected = __DIR__ ."/content/.media";
        $actual = ContentStore::media();
        $this->assertEquals($expected, $actual->unfold());
    }

    public function testMarkdown()
    {
        $expected = 'Hello, World!';
        $actual = ContentStore::uri("/somewhere/else")->markdown();
        $this->assertEquals($expected, $actual->unfold());
    }

    public function testMeta()
    {
        $expected = new \stdClass();
        $expected->title = "Else";
        $expected->created = 20200401;
        $expected->modified = 20200401;
        $expected->moved = 20200401;
        $actual = ContentStore::uri("/somewhere/else")->meta();
        $this->assertEquals($expected, $actual->unfold());
    }

    public function testDetails()
    {
        $expected = [
            "Modified on: Apr 1, 2020",
            "Created on: Apr 1, 2020",
            "Modified on: Apr 1, 2020"
        ];
        $actual = ContentStore::uri("/somewhere/else")
            ->details();
        $this->assertEquals($expected, $actual->unfold());
    }

    public function testToc()
    {
        $expected = '';
        $actual = ContentStore::uri("/toc")
            ->toc();
        $this->assertEquals($expected, $actual->unfold());
    }
}
