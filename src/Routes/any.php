<?php

use Eightfold\ShoopExtras\Shoop;

use Illuminate\Http\Request;

if ($builder->isUsingSiteTracker()) {
    Route::any("{any}", function(Request $request, string $any = null) use ($builder) {
        return $builder->contentStore()->isFile(
            function($result, $store) use ($request, $builder, $any) {
                if (! $result) { abort(404); }
                return ($store->markdown()->meta()->hasMemberUnfolded("redirect"))
                    ? redirect($store->markdown()->meta()->redirect)
                    : view("ef::default")->with("view", $builder->view());
            });
    })->where("any", ".*")->middleware("web");

} else {
    Route::any("{any}", function(Request $request, string $any = null) use ($builder) {
        return $builder->contentStore()->isFile(
            function($result, $store) use ($request, $builder) {
                if (! $result) { abort(404); }

                return ($store->markdown()->meta()->hasMemberUnfolded("redirect"))
                    ? redirect($store->markdown()->meta()->redirect)
                    : view("ef::default")->with("view", $builder->view());
            });
    })->where("any", ".*");
}

