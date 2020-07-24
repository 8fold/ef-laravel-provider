<?php

namespace Eightfold\Site;

use Illuminate\Support\Facades\Route;

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
    static public function saveTransaction(ESStore $store, $sessionId, $hrtime)
    {
        $sessionId = Type::sanitizeType($sessionId, ESString::class);
        $hrtime = Type::sanitizeType($hrtime, ESString::class);

        $store   = $store->plus($sessionId, $hrtime);
        $content = Shoop::dictionary([])->plus(
            // store date and time for page view
            Carbon::now("America/Chicago")->format("YmdHisv"), "timestamp",
            // store the page the user came from
            Shoop::string(url()->previous())->minus(request()->root())->unfold(), "previous",
            // store the page the user came to
            Shoop::string(url()->current())->minus(request()->root())->unfold(), "current"
        )->json();
        $store->saveContent($content);
    }
}
