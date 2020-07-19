<?php

use Carbon\Carbon;
use Eightfold\ShoopExtras\Shoop;
use Eightfold\Events\Events;
use Eightfold\Events\Grid;
use Eightfold\Markup\UIKit;

Route::prefix("events")->group(function() use ($builder) {
    Route::get("/", function() use ($builder) {
        $eventStore = $builder->handler()->eventStore()->unfold();

        $month = Carbon::now()->month;
        $year  = Carbon::now()->year;
        $uri   = Events::init($eventStore)
            ->nearestMonthWithEvents($year, $month)->uri();

        $redirect = Shoop::string("{$eventStore}{$uri}")
            ->replace([$builder->store(true)->unfold() => ""]);

        return redirect("{$redirect}");
    });

    Route::get("/{year}", function($year) use ($builder) {
        $eventStore = $builder->handler()->eventStore()->unfold();

        $path = "{$eventStore}/{$year}";
        return $builder->view(
            UIKit::h1("Events"),
            Grid::forYear($path)->render()
        );
    });

    Route::get("/{year}/{month}", function(string $year, string $month) use ($builder) {
        $eventStore = $builder->handler()->eventStore()->unfold();

        $path = "/{$eventStore}/{$year}/{$month}";
        return $builder->view(
            UIKit::h1("Events"),
            Grid::forMonth($path)->render()
        );
    });
});
