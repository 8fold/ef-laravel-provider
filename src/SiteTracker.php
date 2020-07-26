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
    private $timestamp;

    private $timestampFormat = "YmdGis-v";
    private $datePathFormat = "/Y/m/d/Gis-v";

    private $sessionStore;
    private $sessionPath;

    private $baseContent;

    public function __construct(
        ESStore $store,
        $sessionId,
        $timestampFormat = "YmdGis-v",
        $datePathFormat  = "/Y/m/d/Gis-v"
    )
    {
        $this->store     = $store;
        $this->sessionId = Type::sanitizeType($sessionId, ESString::class);
        $this->time      = Carbon::now("UTC");
        $this->timestamp = $this->time->format($timestampFormat);

        $this->timestampFormat = $timestampFormat;
        $this->datePathFormat  = $datePathFormat;

        $this->sessionStore = $this->store->plus(
            "sessions",
            $this->sessionId,
            $this->timestamp .".pageview"
        );

        $this->sessionPath = $this->sessionStore->string()->minus($this->store);

        $this->baseContent = Shoop::dictionary([])->plus(
            // link session
            $this->sessionPath->unfold(), "session",
            // link time
            $this->timestamp, "timestamp"
        )->json();
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
            // presumed human - always required
            $this->saveSession();

            $this->saveUrl();

            $this->saveDate();
        }
    }

    private function saveCrawler()
    {
        $detect = new CrawlerDetect;
        $crawler = $detect->matches();
        if ($crawler !== null) {
            $store   = $this->store->plus(
                "crawlers",
                Hash::make($crawler),
                $this->timestamp
            );
            $content = Shoop::dictionary([])->plus(
                // store name of crawler
                $crawler, "crawler"
            )->json();
            $store->storeContent($content);
        }
    }

    private function saveSession()
    {
        $content = $this->baseContent->plus(
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
                            list($member, $value) = Shoop::string($memberValue)
                                ->divide("=", false, 2);
                            $params = $params->plus($value, $member);
                        });
                });

        if ($params->isNotEmpty) {
            // url had query parameters, used for keyword searches
            $content = $content->plus($params->object, "params");
        }

        $this->sessionStore->saveContent($content->json());
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
            $this->timestamp .".pageview"
        );

        $store->saveContent($this->baseContent);
    }

    private function saveDate()
    {
        $parts = Shoop::string($this->time->format($this->datePathFormat))->divide("/", false);
        $store = $this->store->plus("dates", ...$parts->dropLast())
            ->plus($parts->last()->plus(".pageview"));

        $store->saveContent($this->baseContent);
    }
}
