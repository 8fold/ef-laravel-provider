<?php

namespace Eightfold\Site;

use Illuminate\Support\ServiceProvider;

use Eightfold\ShoopExtras\Shoop;

class Provider extends ServiceProvider
{
    public function register()
    {
        $this->loadViewsFrom(__DIR__.'/Views', "ef");
    }

    public function boot()
    {
    }
}
