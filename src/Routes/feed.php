<?php

use Carbon\Carbon;

use Eightfold\Shoop\Shoop;

use Eightfold\Markup\Element;

// Route::prefix("feed")->group(function() use ($contentBuilderClass) {
    Route::get("feed/rss", function() use ($contentBuilderClass) {
        $items = $contentBuilderClass::markdown("/feed")->meta()->items()
            ->each(function($path) use ($contentBuilderClass) {
                return $contentBuilderClass::rssItemForPath($path);
        });

        $compiled = Shoop::string("<?xml version=\"1.0\"?>\n")
            ->plus(
                Element::fold(
                    "rss",
                    Element::fold(
                        "channel",
                        Element::fold(
                            "title",
                            $contentBuilderClass::rssChannelTitle()
                        ),
                        Element::fold(
                            "link",
                            "https://". $contentBuilderClass::domain()
                        ),
                        Element::fold(
                            "description",
                            $contentBuilderClass::rssDescription()
                        ),
                        Element::fold("language", "en-us"),
                        Element::fold("copyright", $contentBuilderClass::copyrightContent()),
                        ...$items
                    )
                )->attr("version 2.0")
            );
        return response($compiled)->header("Content-Type", "application/xml");
    });
// });

