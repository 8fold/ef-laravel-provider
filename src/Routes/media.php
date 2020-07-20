<?php

use Eightfold\Site\ContentBuilder;

use Eightfold\Shoop\Shoop;

Route::prefix("media")->group(function() use ($builder) {
    Route::get("/", function() { abort(404); });

    Route::get("/{any}", function($any) use ($builder) {
        Route::get("/", function() { abort(404); });

        $extension = Shoop::string($any)->divide(".")->last;
        if ($extension === "jpg") {
            $extension = "jpeg";
        }

        $parts = Shoop::string($any)->divide("/");
        $path = $builder->handler()->mediaStore()->plus(...$parts);
        return response()->file(
            $path->unfold(),
            ["Content-Type: image/{$extension}"]
        );
    })->where("any", ".*");
});
