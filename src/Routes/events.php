<?php

use Eightfold\ShoopExtras\Shoop;

Route::prefix("events")->group(function() use ($contentBuilderClass) {
    Route::get("/", function() use ($contentBuilderClass) {
        $eventStore = $contentBuilderClass::eventStore()->unfold();

        $month = Carbon::now()->month;
        $year  = Carbon::now()->year;
        $uri   = Events::init($eventStore)
            ->nearestMonthWithEvents($year, $month)->uri();

        $redirect = Shoop::string("{$eventStore}{$uri}")
            ->replace([$contentBuilderClass::rootStore()->unfold() => ""]);

        return redirect("{$redirect}");
    });

    Route::get("/{year}", function($year) use ($contentBuilderClass) {
        $eventStore = $contentBuilderClass::eventStore()->unfold();

        $path = "{$eventStore}/{$year}";
        return $contentBuilderClass::view(
            UIKit::h1("Events"),
            Grid::forYear($path)->render()
        );
    });

    Route::get("/{year}/{month}", function(string $year, string $month) use ($contentBuilderClass) {
        $eventStore = $contentBuilderClass::eventStore()->unfold();

        $path = "/{$eventStore}/{$year}/{$month}";
        return $contentBuilderClass::view(
            UIKit::h1("Events"),
            Grid::forMonth($path)->render()
        );
    });
});
