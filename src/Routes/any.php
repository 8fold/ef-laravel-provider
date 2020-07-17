<?php

use Eightfold\ShoopExtras\Shoop;

Route::any("{any}", function(string $any = null) use ($builder) {
    return $builder->contentStore()->isFile(
        function($result, $store) use ($builder) {
            if (! $result) { abort(404); }

            return ($store->markdown()->meta()->hasMemberUnfolded("redirect"))
                ? redirect($store->markdown()->meta()->redirect)
                : view("ef::default")->with("view", $builder->view());
        });
})->where("any", ".*");
