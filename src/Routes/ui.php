<?php

use Eightfold\Site\ContentBuilder;

use Eightfold\Shoop\Shoop;

Route::prefix("assets/ui")->group(function() use ($contentBuilderClass) {
    Route::get("/", function() { abort(404); });

    Route::get("/{image}", function($image) {
        $extension = Shoop::string($image)->divide(".")->last;

        $path = ContentBuilder::assetsStore()->plus("ui", $image);
        return response()->file(
            $path->unfold(),
            ["Content-Type: image/{$extension}"]
        );
    });
});
