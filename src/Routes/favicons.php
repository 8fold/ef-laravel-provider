<?php

use Eightfold\Site\ContentBuilder;

use Eightfold\Shoop\Shoop;

Route::prefix("assets/favicons")->group(function() use ($contentBuilderClass) {
    Route::get("/", function() { abort(404); });

    Route::get("/{image}", function($image) use ($contentBuilderClass) {
        $extension = Shoop::string($image)->divide(".")->last;
        if ($extension === "ico") {
            $extension = "x-icon";
        }

        $path = $contentBuilderClass::assetsPathParts()
            ->plus($contentBuilderClass::domain(), "favicons", $image)->noEmpties()
            ->join("/")->start("/");
        return response()->file($path, ["Content-Type: image/{$extension}"]);
    });
});
