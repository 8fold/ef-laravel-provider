<?php

namespace Eightfold\Site;

use Eightfold\Shoop\{
    Helpers\Type,
    ESArray
};

use Eightfold\ShoopExtras\{
    Shoop,
    ESPath,
    ESStore
};

class ContentHandler
{
    private $useLocal = true;
    private $localRootPath;
    private $remoteRootPath = "";
    private $githubClient = null;

    static public function fold(ESPath $localRootPath, ESPath $remoteRootPath = null)
    {
        return new ContentHandler($localRootPath, $remoteRootPath);
    }

    static public function markdownConfig()
    {
        return Shoop::dictionary([
            "html_input" => "strip",
            "allow_unsafe_links" => false
        ])->plus(Shoop::dictionary(["open_in_new_window" => true]), "external_link")
        ->plus(Shoop::dictionary(["symbol" => "#"]), "heading_permalink")
        ->unfold();
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

    public function titles($checkHeadingFirst = true, $parts = []): ESArray
    {
        $parts = Type::sanitizeType($parts, ESArray::class);

        $useRoot = $parts->countIsLessThanUnfolded(1);

        $store = $this->store($useRoot, ...$parts);

        return $parts->each(function($part) use (&$store, $checkHeadingFirst) {
            $s = $store->plus("content.md");
            if ($checkHeadingFirst) {
                $title = $s->metaMember("heading")->countIsGreaterThan(0,
                    function($result, $title) use ($s) {
                        var_dump($s->markdown());
                        die(var_dump($s->metaMember("heading")));
                        return ($result->unfold())
                            ? $title
                            : $s->metaMember("title");
                    });

            } else {
                $title = $s->metaMember("title");

            }

            if ($store->parts()->countIsGreaterThanUnfolded(0)) {
                $store = $store->dropLast();
            }

            return $title;

        })->noEmpties()->plus(
            $this->contentStore(true)->metaMember("title")
        );

        // if ($parts->isNotEmpty) {
        //     $store = static::rootStore()->plus(...$parts);
        // }

        // if (static::rootUri()->isUnfolded("events")) {
        //     $store = static::eventStore();
        // }
// die(var_dump($store));
        return $store->isNotFile(
            function($result, $store) use ($checkHeadingFirst, $parts) {
                if ($result->unfold()) { return Shoop::array([]); }

                if ($parts->count()->isUnfolded(1) and
                    $parts->first()->isEmpty
                ) {
                    if ($checkHeadingFirst and $store->metaMember("heading")->isNotEmpty) {
                        return Shoop::array([
                            $store->metaMember("heading")
                        ]);
                    }

                    $title = $store->metaMember("title");
                    if ($title->isEmpty) {
                        $title = Shoop::string("");
                    }
                    return Shoop::array([$title]);
                }

                $s = $store->dropLast();

                return $parts->each(function($part) use (&$s, $checkHeadingFirst) {
                    $content = $s->plus("content.md");

                    $return = "";
                    if ($checkHeadingFirst and
                        $s->metaMember("heading")->isNotEmpty
                    ) {
                        $return = $content->metaMember("heading");

                    } else {
                        $return = $content->metaMember("title");
                        if ($return->isEmpty) {
                            $return = "";

                        }
                    }

                    $s = $s->dropLast();
                    return $return;

                })->noEmpties()->plus(
                    // (static::useLocal())
                    //     ? static::rootStore()->plus("content.md")
                    //         ->metaMember("title")->unfold()
                    //     : static::githubClient(true)->plus("content.md")
                    //         ->metaMember("title")->unfold()
                );
        });
    }
}
