<?php

namespace Eightfold\Site\Tests;

use Orchestra\Testbench\BrowserKit\TestCase;
// use Orchestra\Testbench\TestCase;

use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;

use Eightfold\Site\Tests\TestContentBuilder as ContentBuilder;

use Eightfold\Site\SiteTracker;

use Eightfold\ShoopExtras\Shoop;

class SiteTrackerTest extends TestCase
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

    public function testBuilder()
    {
        // $builder = TestContentBuilder::fold(
        //     Shoop::path(__DIR__)->plus("content")
        // )->useSiteTracker(true);
        // $this->assertNotNull($builder);

        $this->visit("/");//->click("somewhere")->click("else");
        $this->visit("/somewhere");
        $this->visit("/somewhere/else");
        // $actual = (new SiteTracker)->trackerItem();
    }

    // public function testTrackerItem()
    // {
    //     $this->visit("/");
    //     $tracker = new SiteTracker;
    //     // $actual = $tracker->trackerItem();

    //     $this->visit("/");
    //     $this->visit("/somewhere");
    //     $this->visit("/somewhere/else");
    //     $actual = $tracker->trackerItem();
    // }
}
