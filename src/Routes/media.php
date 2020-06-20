<?php

use Eightfold\Site\ContentBuilder;

use Eightfold\Shoop\Shoop;

Route::prefix("media")->group(function() use ($contentBuilderClass) {
    Route::get("/", function() { abort(404); });

    Route::get("/{any}", function($any) {
        Route::get("/", function() { abort(404); });

        $extension = Shoop::string($any)->divide(".")->last;
        $parts = Shoop::string($any)->divide("/");
        $path = ContentBuilder::mediaStore()->plus(...$parts);
        return response()->file(
            $path->unfold(),
            ["Content-Type: image/{$extension}"]
        );
    })->where("any", ".*");
});
