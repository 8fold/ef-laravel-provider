<?php

namespace Eightfold\Site;

use Carbon\Carbon;

use Eightfold\Shoop\{
    Helpers\Type,
    ESArray,
    ESString
};

use Eightfold\ShoopExtras\{
    Shoop,
    ESPath,
    ESStore
};

class ContentHandler
{
    /**
     * Title member from YAML front matter.
     */
    public const TITLE = "title";

    /**
     * Heading member from YAML front matter, falls back to title member,
     * if heading not set.
     */
    public const HEADING = "heading";

    /**
     * Recursively uses title member from YAML front matter to build a fully-
     * qualified title string with separator. ex. Leaf | Branch | Trunk | Root
     */
    public const PAGE = "page";

    /**
     * @deprecated
     *
     * Uses the title member from YAML front matter to build a two-part title,
     * which includes the title for the current URL plus the title of the root
     * page with a separater. ex. Leaf | Root
     */
    public const SHARE = "share";

    /**
     * Uses the title member from YAML front matter to build a two-part title,
     * which includes the title for the current URL plus the title of the root
     * page with a separater. ex. Leaf | Root
     */
    public const BOOKEND = "book-end";

    private $useLocal = true;
    private $localRootPath;
    private $remoteRootPath = "";
    private $githubClient = null;

    static public function fold(ESPath $localRootPath, ESPath $remoteRootPath = null)
    {
        return new static($localRootPath, $remoteRootPath);
    }

    static public function uri($parts = false) // :ESString|ESArray
    {
        $base = Shoop::uri(request()->url())->tail();
        return ($parts) ? $base->divide("/")->noEmpties()->reindex() : $base;
    }

    static public function rootUri(): ESString
    {
        return static::uri(true)->isEmpty(function($result, $array) {
            return ($result->unfold()) ? Shoop::string("") : $array->first();
        });
    }

    public function __construct(ESPath $localRootPath, ESPath $remoteRootPath = null)
    {
        $this->localRootPath = $localRootPath;

        if ($this->remoteRootPath !== null) {
            $this->remoteRootPath = $remoteRootPath;
            $ghToken = env("GITHUB_PERSONAL_TOKEN");
            $ghUsername = env("GITHUB_USERNAME");
            $ghRepo = env("GITHUB_REPO");
            if (
                $ghToken !== null and
                $ghUsername !== null and
                $ghRepo !== null and
                $remoteRootPath !== null
            )
            {
                $this->useLocal = false;
                $this->githubClient = Shoop::github(
                    $remoteRootPath,
                    $ghToken,
                    $ghUsername,
                    $ghRepo,
                    $localRootPath,
                    ".cache"
                );
            }

        } else {
            $this->remoteRootPath = Shoop::path("");

        }
    }

    public function useLocal()
    {
        return $this->useLocal;
    }

    public function localRoot(): ESPath
    {
        return $this->localRootPath;
    }

    public function remoteRoot(): ESPath
    {
        return $this->remoteRoot;
    }

    public function githubClient()
    {
        return $this->githubClient;
    }

    public function store(bool $useRoot = false, ...$plus): ESPath
    {
        $store = ($this->useLocal())
            ? Shoop::store($this->localRoot())
            : $this->githubClient();

        if (! $useRoot) {
            $parts = Shoop::path(request()->path())->parts();
            if ($parts->countIsGreaterThanUnfolded(0)) {
                $store = $store->plus(...$parts);
            }
        }

        if ($useRoot or count($plus) > 0) {
            if (Shoop::array($plus)->countIsGreaterThanUnfolded(0)) {
                $store = $store->plus(...$plus);
            }
        }
        return $store;
    }

    public function contentStore(bool $useRoot = false, ...$plus): ESPath // ESStore|ESGitHubClient
    {
        return $this->store($useRoot, ...$plus)->plus("content.md");
    }

    public function assetsStore(...$plus): ESPath
    {
        return $this->store(true, ".assets")->plus(...$plus);
    }

    public function mediaStore(...$plus): ESPath
    {
        return static::store(true, ".media")->plus(...$plus);
    }

    public function eventStore(...$plus): ESPath
    {
        return static::store(true, "events")->plus(...$plus);
    }

    public function title($type = "", $checkHeadingFirst = true, $parts = []): ESString
    {
        if (strlen($type) === 0) {
            $type = static::PAGE;
        }

        $parts = Type::sanitizeType($parts, ESArray::class);
        if ($parts->isEmpty) {
            $parts = static::uri(true);
        }

        $titles = Shoop::array([]);
        if ($checkHeadingFirst and
            Shoop::string(static::HEADING)->isUnfolded($type)
        ) {
            $titles = $titles->plus(
                $this->titles($checkHeadingFirst, $parts)->first()
            );

        } elseif (Shoop::string(static::TITLE)->isUnfolded($type)) {
            $titles = $titles->plus(
                $this->titles(false, $parts)->first()
            );

        } elseif (Shoop::string(static::BOOKEND)->isUnfolded($type)) {
            if ($this->uri(true)->isEmpty) {
                $titles = $titles->plus(
                    $this->titles($checkHeadingFirst, $parts)->first()
                );

            } else {
                $t = $this->titles($checkHeadingFirst, $parts)->divide(-1);
                $start = $t->first()->first();
                $root = $t->last()->first();
                if ($this->uri(true)->isUnfolded("events")) {
                    $eventTitles = $this->eventsTitles();
                    $start = $start->start($eventTitles->month ." ". $eventTitles->year);
                    $root = $this->contentStore(true)->markdown()->meta()->title();
                }

                $titles = $titles->plus($start, $root);
            }

        } elseif (Shoop::string(static::PAGE)->isUnfolded($type)) {
            $t = $this->titles($checkHeadingFirst, $parts)->divide(-1);
            $start = $t->first();
            $root = $t->last();
            if ($this->uri(true)->isUnfolded("events")) {
                die("here");
                $eventTitles = $this->eventsTitles(
                    $type = "",
                    $checkHeadingFirst = true,
                    $parts = []
                );
                $start = $start->start($eventTitles->month, $eventTitles->year);
            }
            $titles = $titles->plus(...$start)->plus(...$root);

        }
        return $titles->noEmpties()->join(" | ");
    }

    public function titles($checkHeadingFirst = true, $parts = []): ESArray
    {
        $parts = Type::sanitizeType($parts, ESArray::class);

        // $useRoot = $parts->countIsLessThanUnfolded(1);

        $store = $this->store(true, ...$parts);

        return $parts->each(function($part) use (&$store, $checkHeadingFirst) {
            $s = $store->plus("content.md");
            $title = (! $checkHeadingFirst)
                ? $s->metaMember("title")
                : $s->metaMember("heading")->countIsGreaterThan(0,
                    function($result, $title) use ($s) {
                        return ($result->unfold())
                            ? $title
                            : $s->metaMember("title");
                    });

            if ($store->parts()->countIsGreaterThanUnfolded(0)) {
                $store = $store->dropLast();
            }
            return $title;

        })->noEmpties()->plus(
            $this->contentStore(true)->metaMember("title")
        );
    }

   public function eventsTitles($checkHeadingFirst = true, $parts = [])
    {
        $parts = static::uri();
        $year  = $parts->dropFirst()->first;
        $month = $this->dateString($parts->dropFirst(2)->first, "m", "F");

        return Shoop::dictionary([
            "year"  => $year,
            "month" => $month
        ]);
    }

    public function details()
    {
        $meta = $this->contentStore()->markdown()->meta();

        $return = Shoop::dictionary([]);
        $return = $return->plus(
            ($meta->created === null)
                ? Shoop::string("")
                : $this->dateString($meta->created),
            "created"
        );

        $return = $return->plus(
            ($meta->modified === null)
                ? Shoop::string("")
                : $this->dateString($meta->modified),
            "modified"
        );

        $return = $return->plus(
            ($meta->moved === null)
                ? Shoop::string("")
                : $this->dateString($meta->moved),
            "moved"
        );

        $return = $return->plus(
            ($meta->original === null)
                ? Shoop::string("")
                : Shoop::string($meta->original),
            "original"
        );

        return $return->noEmpties();
    }

    public function copyright($name, $startYear = ""): ESString
    {
        if (strlen($startYear) > 0) {
            $startYear = $startYear ."&ndash;";
        }
        return Shoop::string("Copyright © {$startYear}". date("Y") ." {$name}. All rights reserved.");
    }

    private function dateString(
        string $yyyymmdd,
        string $startFormat = "Ymd",
        string $endFormat = ""
    ): ESString
    {
        if (empty($endFormat)) {
            return Shoop::string(
                Carbon::createFromFormat("Ymd", $yyyymmdd, "America/Chicago")
                    ->toFormattedDateString()
            );
        }
        return Shoop::string(
            Carbon::createFromFormat($startFormat, $yyyymmdd, "America/Chicago")
                ->format($endFormat)
        );
    }

    public function description(): ESString
    {
        $description = static::store()->plus("content.md")
            ->markdown()->meta()->description;
        $description = ($description === null)
            ? Shoop::string("")
            : Shoop::string($description);

        return $description->isNotEmpty(function($result, $description) {
            if ($result->unfold()) {
                return Shoop::string($description);
            }
            return $this->descriptionImmediateFallback()->isNotEmpty(
                function($result, $description) {
                    return ($result->unfold())
                        ? $description
                        : $this->title(static::BOOKEND);
            });
        });
    }

    public function descriptionImmediateFallback(): ESString
    {
        return Shoop::string("");
    }

    public function socialImage(): ESString
    {
        $parts = $this->uri(true);
        $store = $this->mediaStore()->plus(...$parts);
        return $parts->each(function($part, $index, &$break) use (&$store) {
            $poster = $store->plus("poster.jpg");
            if ($poster->isFile) {
                $break = true;
                return Shoop::string($store)->minus($this->mediaStore())
                        ->start(request()->root(), "/media")->plus("/poster.jpg");

            } else {
                $store = $store->dropLast();
                return "";

            }
        })->noEmpties()->isEmpty(function($result, $array) {
            return ($result->unfold())
                ? Shoop::string(request()->root())->plus("/media/poster.jpg")
                : $array->first();
        });
    }
}
