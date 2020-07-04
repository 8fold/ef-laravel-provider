<?php

use Eightfold\ShoopExtras\Shoop;

Route::any("{any}", function(string $any = null) use ($contentBuilderClass) {
    return $contentBuilderClass::store("content.md")->isFile(
        function($result, $path) use ($contentBuilderClass) {
            if (! $result) {
                abort(404);
            }

            $store = Shoop::store($path);
            return ($store->markdown()->meta()->hasMemberUnfolded("redirect"))
                ? redirect($store->markdown()->meta()->redirect)
                : view("ef::default")->with("view", $contentBuilderClass::view());
        });
})->where("any", ".*");
