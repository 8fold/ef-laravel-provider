<?php

use Eightfold\Site\ContentBuilder;

use Eightfold\Shoop\Shoop;

Route::any("{any}", function(string $any = null) use ($contentBuilderClass) {
    $contentBuilderClass::markdown()->string()->isEmpty(function($result) {
        if ($result) {
            abort(404);
        }
    });

    if ($contentBuilderClass::markdown()->meta()->hasMemberUnfolded("redirect")) {
        return redirect($contentBuilderClass::markdown()->meta()->redirect);
    }
    return view("ef::default")->with("view", $contentBuilderClass::view());
})->where("any", ".*");
