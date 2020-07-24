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
    private $store;
    private $sessionId;
    private $time;

    public function __construct(ESStore $store, $sessionId, $time)
    {
        $this->store     = $store;
        $this->sessionId = Type::sanitizeType($sessionId, ESString::class);
        $this->time      = Type::sanitizeType($time, ESString::class);
    }

    /**
     * @todo be able to specify saving combinations
     * - crawlers-only
     * - sessions-only
     * - sessions and urls-only: urls depend on sessions
     */
    public function saveTransaction(): void
    {
        // detect if and what action is needed
        $detect = new CrawlerDetect;
        if ($detect->isCrawler()) {
            // presumed web crawler or bot
            $this->saveCrawler();

        } elseif (! $detect->isCrawler()) {
            // presumed human
            $this->saveSession();

            $this->saveUrl();
        }
    }

    private function saveCrawler()
    {
        $detect = new CrawlerDetect;
        $crawler = $detect->matches();
        if ($crawler !== null) {
            $store   = $this->store->plus("crawlers", Hash::make($crawler), $hrtime);
            $content = Shoop::dictionary([])->plus(
                // store name of crawler
                $crawler, "crawler"
            )->json();
            $store->storeContent($content);
        }
    }

    private function saveSession()
    {
        $store   = $this->store->plus("sessions", $this->sessionId, $this->time);

        $content = Shoop::dictionary([])->plus(
            // store date and time for page view
            Carbon::now("America/Chicago")->format("YmdHisv"), "timestamp",
            // store the page the user came from
            Shoop::string(url()->previous())->minus(request()->root())->unfold(), "previous",
            // store the page the user came to
            Shoop::string(url()->current())->minus(request()->root())->unfold(), "current",
        );

        // url query parameters
        $params = Shoop::dictionary([]);
        Shoop::string(request()->fullUrl())->divide("?", false, 2)
            ->countIsLessThanOrEqualTo(1,
                function($result, $divided) use (&$params) {
                    if ($result->unfold()) { return; }
                    $divided->last()->divide("&", false)->each(
                        function($memberValue) use (&$params) {
                            list($member, $value) = Shoop::string($memberValue)->divide("=", false, 2);
                            $params = $params->plus($value, $member);
                        });
                });

        if ($params->isNotEmpty) {
            // url had query parameters, used for keyword searches
            $content = $content->plus($params->object, "params");
        }

        $store->saveContent($content->json());
    }

    private function saveUrl()
    {
        $store   = $this->store->plus(
            "urls",
            Shoop::string(url()->current())->minus(request()->root())->isEmpty(
                function($result, $string) {
                    return ($result->unfold())
                        ? "root"
                        : $string->startsWith("/", function($result, $string) {
                            return ($result->unfold())
                                ? $string->dropFirst()
                                : $string;
                        });
                }),
            $this->time
        );
        $content = Shoop::dictionary([])->plus(
            // link session
            $this->sessionId, "session",
            // link time
            $this->time, "timestamp"
        )->json();
        $store->saveContent($content);
    }
}
