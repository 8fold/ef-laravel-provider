<?php

use Eightfold\Site\ContentBuilder;

use Eightfold\Shoop\Shoop;

Route::prefix("assets/favicons")->group(function() use ($contentBuilderClass) {
    Route::get("/", function() { abort(404); });

    Route::get("/{image}", function($image) {
        $extension = Shoop::string($image)->divide(".")->last;
        if ($extension === "ico") {
            $extension = "x-icon";
        }

        $path = ContentBuilder::assetsStore()->plus("favicons", $image);
        return response()->file(
            $path->unfold(),
            ["Content-Type: image/{$extension}"]
        );
    });
});
