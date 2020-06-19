<?php

namespace Eightfold\Site\Tests;

use Illuminate\Support\ServiceProvider;

use Eightfold\ShoopExtras\Shoop;

class TestProvider extends ServiceProvider
{
    public function register()
    {
        $root = Shoop::store(__DIR__);
        $views = $root->dropLast()->plus("src", "Views");
        $routes = $root->plus("TestRoutes.php");
        $this->loadViewsFrom($views->unfold(), "ef");
        $this->loadRoutesFrom($routes->unfold());
    }

    public function boot()
    {
    }
}
