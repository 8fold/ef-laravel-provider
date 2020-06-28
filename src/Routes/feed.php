<?php

use Carbon\Carbon;

use Eightfold\ShoopExtras\Shoop;

use Eightfold\Markup\Element;

Route::prefix("feed")->group(function() use ($contentBuilderClass) {
    Route::get("/", function() use ($contentBuilderClass) {
        return $contentBuilderClass::uriContentStore("/feed")->isFile(
            function($result, $store) use ($contentBuilderClass) {
                if (! $result) {
                    abort(404);
                }

                return ($store->markdown()->meta()->hasMemberUnfolded("redirect"))
                    ? redirect($store->markdown()->meta()->redirect)
                    : view("ef::default")
                        ->with("view", $contentBuilderClass::uriTocView());
            });
    });

    Route::get("/page/1", function() use ($contentBuilderClass) {
        return redirect("/feed");
    });

    Route::get("/page/{currentPage}", function($currentPage) use ($contentBuilderClass) {
        return $contentBuilderClass::uriContentStore("/feed")->isFile(
            function($result, $store) use ($contentBuilderClass, $currentPage) {
                if (! $result) {
                    abort(404);
                }

                return ($store->markdown()->meta()->hasMemberUnfolded("redirect"))
                    ? redirect($store->markdown()->meta()->redirect)
                    : view("ef::default")
                        ->with("view", $contentBuilderClass::uriTocView($currentPage));
            });
    });

    Route::get("/rss", function() use ($contentBuilderClass) {
        return response($contentBuilderClass::rssCompiled()->unfold())
            ->header("Content-Type", "application/xml");
    });
});

