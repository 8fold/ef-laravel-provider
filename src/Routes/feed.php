<?php

use Carbon\Carbon;

use Eightfold\Shoop\Shoop;

use Eightfold\Markup\Element;

Route::prefix("feed")->group(function() use ($contentBuilderClass) {
    Route::get("/rss", function() use ($contentBuilderClass) {
        return response($contentBuilderClass::rssCompiled()->unfold())
            ->header("Content-Type", "application/xml");
    });
});

