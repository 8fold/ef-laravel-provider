<?php

use Eightfold\Site\ContentBuilder;

use Eightfold\Shoop\Shoop;

Route::prefix("assets/favicons")->group(function() use ($builder) {
    Route::get("/", function() { abort(404); });

    Route::get("/{image}", function($image) use ($builder) {
        $extension = Shoop::string($image)->divide(".")->last;
        if ($extension === "ico") {
            $extension = "x-icon";
        }

        $path = $builder->handler()->assetsStore()->plus("favicons", $image);
        return response()->file(
            $path->unfold(),
            ["Content-Type: image/{$extension}"]
        );
    });
});
