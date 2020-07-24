<?php

namespace Eightfold\Site;

use Jaybizzle\CrawlerDetect\CrawlerDetect;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;

use Carbon\Carbon;

use Eightfold\Shoop\{
    Helpers\Type,
    ESString
};

use Eightfold\ShoopExtras\{
    Shoop,
    ESStore
};

class SiteTracker
{
    static public function saveTransaction(ESStore $store, $sessionId, $hrtime): void
    {
        $hrtime = Type::sanitizeType($hrtime, ESString::class);


        $detect = new CrawlerDetect;
        if ($detect->isCrawler()) {
            // presumed web crawler or bot, get name
            $crawler = $detect->matches();
            if ($crawler !== null) {
                $store   = $store->plus("crawlers", Hash::make($crawler), $hrtime);
                $content = Shoop::dictionary([])->plus(
                    // store name of crawler
                    $crawler, "crawler"
                )->json();
                $store->storeContent($content);
            }

        } elseif (! $detect->isCrawler()) {
            // presumed human
            $sessionId = Type::sanitizeType($sessionId, ESString::class);

            $store   = $store->plus("sessions", $sessionId, $hrtime);
            $content = Shoop::dictionary([])->plus(
                // store date and time for page view
                Carbon::now("America/Chicago")->format("YmdHisv"), "timestamp",
                // store the page the user came from
                Shoop::string(url()->previous())->minus(request()->root())->unfold(), "previous",
                // store the page the user came to
                Shoop::string(url()->current())->minus(request()->root())->unfold(), "current"
            )->json();
            $store->saveContent($content);

            $store   = $store->plus("urls", Hash::make(url()->current()), $hrtime);
            $content = Shoop::dictionary([])->plus(
                // link session
                $sessionId, "session",
                // link time
                $hrtime, "timestamp"
            )->json();
            $store->saveContent($content);
        }
    }
}
