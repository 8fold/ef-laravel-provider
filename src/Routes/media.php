<?php

use Eightfold\Site\ContentBuilder;

use Eightfold\Shoop\Shoop;

Route::prefix("media")->group(function() use ($contentBuilderClass) {
    Route::get("/", function() { abort(404); });

    Route::get("/{any}", function($any) use ($contentBuilderClass) {
        $extension = Shoop::string($image)->divide(".")->last;

        $path = $contentBuilderClass::assetsPathParts()
            ->plus($contentBuilderClass::domain(), ...Shoop::string($image)->divide("/"))
            ->join("/")->start("/");

        return response()->file($path, ["Content-Type: image/{$extension}"]);
    })->where("any", ".*");
});
