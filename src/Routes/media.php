<?php

use Eightfold\Site\ContentBuilder;

use Eightfold\Shoop\Shoop;

Route::prefix("media")->group(function() use ($contentBuilderClass) {
    Route::get("/", function() { abort(404); });

    Route::get("/{any}", function($any) use ($contentBuilderClass) {
        $routeParts = Shoop::string($any)->divide("/");
        $path = $contentBuilderClass::contentPathParts()->dropLast()
            ->plus("media", $contentBuilderClass::domain(), ...$routeParts)
            ->join("/")->start("/");
        $extension = Shoop::string($any)->divide(".")->last;
        return response()->file($path, ["Content-Type: image/{$extension}"]);
    })->where("any", ".*");
});
