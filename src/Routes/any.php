<?php

use Eightfold\Site\ContentBuilder;

use Eightfold\Shoop\Shoop;

Route::any("{any}", function(string $any = null) use ($contentBuilderClass) {
    $store = $contentBuilderClass::uriContentStore();
    return $store->isFile(function($result) use ($store, $contentBuilderClass) {
        if (! $result) {
            abort(404);
        }
        return ($store->markdown()->meta()->hasMemberUnfolded("redirect"))
            ? redirect($store->markdown()->meta()->redirect)
            : view("ef::default")->with("view", $contentBuilderClass::view());
    });
})->where("any", ".*");
