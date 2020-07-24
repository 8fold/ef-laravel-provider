<?php

use Carbon\Carbon;

use Eightfold\ShoopExtras\Shoop;

use Eightfold\Markup\Element;

if ($builder->isUsingSiteTracker()) {
    Route::prefix("feed")->group(function() use ($builder) {
        Route::get("/", function() use ($builder) {
            return $builder->contentStore()->isFile(
                function($result, $store) use ($builder) {
                    if (! $result->unfold()) { abort(404); }

                    return ($store->markdown()->meta()->hasMemberUnfolded("redirect"))
                        ? redirect($store->markdown()->meta()->redirect)
                        : view("ef::default")
                            ->with("view", $builder->tocView());
                });
        })->middleware("web");

        Route::get("/page/1", function() {
            return redirect("/feed");
        })->middleware("web");

        Route::get("/page/{currentPage}", function($currentPage) use ($builder) {
            return $builder->handler()->contentStore("/feed")->isFile(
                function($result, $store) use ($builder, $currentPage) {
                    if (! $result) { abort(404); }

                    return ($store->markdown()->meta()->hasMemberUnfolded("redirect"))
                        ? redirect($store->markdown()->meta()->redirect)
                        : view("ef::default")
                            ->with("view", $builder->tocView($currentPage));
                });
        })->middleware("web");

        Route::get("/rss", function() use ($builder) {
            return response($builder->rss()->unfold())
                ->header("Content-Type", "application/xml");
        })->middleware("web");
    });

} else {
    Route::prefix("feed")->group(function() use ($builder) {
        Route::get("/", function() use ($builder) {
            return $builder->contentStore()->isFile(
                function($result, $store) use ($builder) {
                    if (! $result->unfold()) { abort(404); }

                    return ($store->markdown()->meta()->hasMemberUnfolded("redirect"))
                        ? redirect($store->markdown()->meta()->redirect)
                        : view("ef::default")
                            ->with("view", $builder->tocView());
                });
        });

        Route::get("/page/1", function() {
            return redirect("/feed");
        });

        Route::get("/page/{currentPage}", function($currentPage) use ($builder) {
            return $builder->handler()->contentStore("/feed")->isFile(
                function($result, $store) use ($builder, $currentPage) {
                    if (! $result) { abort(404); }

                    return ($store->markdown()->meta()->hasMemberUnfolded("redirect"))
                        ? redirect($store->markdown()->meta()->redirect)
                        : view("ef::default")
                            ->with("view", $builder->tocView($currentPage));
                });
        });

        Route::get("/rss", function() use ($builder) {
            return response($builder->rss()->unfold())
                ->header("Content-Type", "application/xml");
        });
    });
}

