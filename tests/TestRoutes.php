<?php

namespace Eightfold\Site\Tests;

use Illuminate\Support\Facades\Route;

use Eightfold\ShoopExtras\Shoop;

$contentBuilderClass = TestContentBuilder::class;
Route::get("/somewhere/else", function() {
    return view("welcome");
});
include(Shoop::store(__DIR__)->dropLast()->plus("src", "Routes", "feed.php")->unfold());
include(Shoop::store(__DIR__)->dropLast()->plus("src", "Routes", "any.php")->unfold());

