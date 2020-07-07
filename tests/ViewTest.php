<?php

use Orchestra\Testbench\BrowserKit\TestCase;

use Eightfold\Site\Tests\TestContentBuilder as ContentBuilder;

use Eightfold\Shoop\Shoop;
use Eightfold\Markup\UIKit;

use Eightfold\Site\Views\PosterImage;

class ViewTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return ['Eightfold\Site\Tests\TestProvider'];
    }

    public function testPosterImage()
    {
        $uri = "/";
        $this->visit("/");
        $expected = UIKit::image(
            "The eightfold logo.",
            "http://localhost/poster.jpg"
        );
        $actual = PosterImage::view(ContentBuilder::class, $uri);
        $this->assertEquals($expected->unfold(), $actual->unfold());

        // $uri = "/somewhere/else";
        // $this->visit($uri);
        // $expected = Shoop::string('');
        // $actual = PosterImage::view(ContentBuilder::class, $uri);
        // $this->assertEquals($expected->unfold(), $actual->unfold());

        // $this->visit($uri);
        // $expected = '';
        // $actual = PosterImage::view(ContentBuilder::class, $uri, true);
        // $this->assertEquals($expected, $actual->unfold());
    }
}
