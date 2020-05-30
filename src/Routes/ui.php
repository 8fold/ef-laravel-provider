<?php

use Eightfold\Site\ContentBuilder;

use Eightfold\Shoop\Shoop;

Route::prefix("assets/ui")->group(function() use ($contentBuilderClass) {
    Route::get("/", function() { abort(404); });

    Route::get("/{image}", function($image) use ($contentBuilderClass) {
        $extension = Shoop::string($image)->divide(".")->last;

        $path = $contentBuilderClass::contentPathParts()->dropLast()
            ->plus(".assets", $contentBuilderClass::domain(), "ui", $image)
            ->join("/")->start("/");
        return response()->file($path, ["Content-Type: image/{$extension}"]);
    });
});
