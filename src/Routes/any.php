<?php

use Eightfold\ShoopExtras\Shoop;

use Eightfold\Site\Helpers\Uri;

Route::any("{any}", function(string $any = null) use ($contentBuilderClass) {
    return $contentBuilderClass::contentStore()->isFile(
        function($result, $store) use ($contentBuilderClass) {
            if (! $result) {
                abort(404);
            }
            return ($store->markdown()->meta()->hasMemberUnfolded("redirect"))
                ? redirect($store->markdown()->meta()->redirect)
                : view("ef::default")->with("view", $contentBuilderClass::view());
        });
})->where("any", ".*");
