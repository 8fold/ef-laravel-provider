<?php

namespace Eightfold\Site\Tests;

use Illuminate\Support\Facades\Route;

use Eightfold\ShoopExtras\Shoop;

$contentBuilderClass = TestContentBuilder::class;

// include(base_path("vendor/8fold/laravel-provider/src/Routes/favicons.php"));
// include(base_path("vendor/8fold/laravel-provider/src/Routes/ui.php"));
// include(base_path("vendor/8fold/laravel-provider/src/Routes/feed.php"));
include(Shoop::store(__DIR__)->dropLast()->plus("src", "Routes", "any.php")->unfold());
