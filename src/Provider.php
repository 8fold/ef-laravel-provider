<?php

namespace Eightfold\Site;

use Illuminate\Support\ServiceProvider;

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
