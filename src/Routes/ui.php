<?php

use Eightfold\Site\ContentBuilder;

use Eightfold\Shoop\Shoop;

Route::prefix("assets/ui")->group(function() use ($builder) {
    Route::get("/", function() { abort(404); });

    Route::get("/{image}", function($image) use ($builder) {
        $extension = Shoop::string($image)->divide(".")->last;

        $path = $builder->handler()->assetsStore()->plus("ui", $image);
        return response()->file(
            $path->unfold(),
            ["Content-Type: image/{$extension}"]
        );
    })->name("assets");
});
