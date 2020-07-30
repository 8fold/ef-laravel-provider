<?php

namespace Eightfold\Site;

use Carbon\Carbon;

// use Github\Client;
// use League\Flysystem\Adapter\Local;
// use League\Flysystem\Filesystem;
// use Cache\Adapter\Filesystem\FilesystemCachePool;


use Spatie\YamlFrontMatter\YamlFrontMatter;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\DocParser;
use League\CommonMark\Environment;
use League\CommonMark\HtmlRenderer;

// available extensions
use League\CommonMark\Extension\{
    Strikethrough\StrikethroughExtension,
    Table\TableExtension,
    TaskList\TaskListExtension
};

use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\SmartPunct\SmartPunctExtension;

use Eightfold\CommonMarkAbbreviations\AbbreviationExtension;
use Eightfold\CommonMarkAccorions\AccordionsExtension;

// end extensions

use Eightfold\ShoopExtras\{
    Shoop,
    ESStore,
    ESPath
};

use Eightfold\Shoop\Helpers\Type;

use Eightfold\Shoop\{
    ESArray,
    ESBool,
    ESString,
    ESInt
};

use Eightfold\Markup\UIKit;
use Eightfold\Markup\Element;
use Eightfold\Markup\UIKit\Elements\Compound\WebHead;

use Eightfold\Site\ContentHandler;
use Eightfold\Site\SiteTracker;

abstract class ContentBuilder
{
    private $useSiteTracker = false;

    static public function fold(ESPath $localRootPath)
    {
        return new static($localRootPath);
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

    static public function markdownExtensions()
    {
        return Shoop::array([
            StrikethroughExtension::class,
            TableExtension::class,
            TaskListExtension::class,
            ExternalLinkExtension::class,
            SmartPunctExtension::class,
            AbbreviationExtension::class,
            AccordionsExtension::class
        ]);
    }

    protected $handler;

    public function __construct(ESPath $localRootPath)
    {
        $this->handler = ContentHandler::fold($localRootPath);
    }

    public function handler()
    {
        return $this->handler;
    }

    public function contentStore(bool $useRoot = false, ...$plus)
    {
        return $this->handler()->contentStore($useRoot, ...$plus);
    }

    public function view(...$extras)
    {
        if ($this->contentStore()->isNotFile) {
            abort(404);
        }

        if ($this->isUsingSiteTracker()) {
            (new SiteTracker(
                $this->handler()->trackerStore(),
                session()->getId())
            )->saveTransaction();
        }

        return UIKit::webView(
                $this->handler()->title(),
                UIKit::main($this->markdown(), ...$extras)
            )->meta($this->meta());
    }

    /**
     * Allows site tracking to be turned on and off within routes.
     */
    public function useSiteTracker(bool $use = false)
    {
        $this->useSiteTracker = $use;
        return $this;
    }

    /**
     * Allows optional middleware application.
     *
     * Note: Not sure if there is an alternative method to accomplish the same result,
     *     and not setting the middleware did not create a consistent session.
     */
    public function isUsingSiteTracker()
    {
        return $this->useSiteTracker;
    }

// - Extra UI
    public function markdown()
    {
        return UIKit::markdown(
            $this->contentStore()->markdown()->content()->unfold(),
            static::markdownConfig()
        )->prepend(
            UIKit::h1(
                $this->handler()->title(
                    ContentHandler::HEADING
                )->unfold()
            )->unfold() ."\n\n".
            $this->detailsView() ."\n\n"
        )->extensions(...static::markdownExtensions());
    }

    public function detailsView()
    {
        $details = $this->handler()->details();

        $copy = Shoop::string("");
        if ($details->hasMemberUnfolded("created")) {
            // Created on Apr 1, 2020 - if created, required
            $copy = $copy->plus("Created on ")->plus($details->created);
        }

        if ($details->hasMemberUnfolded("modified")) {
            // (updated Apr 1, 2020) - if modified
            $copy = $copy->plus(" (updated ")->plus($details->modified)->plus(")");
        }

        if ($details->hasMemberUnfolded("original")) {
            // , which was<br> originally posted on <a href="https://8fold.pro">8fold</a> - if original
            list($href, $title) = $details->original()->divide(" ", true, 2);
            $copy = $copy->plus(", which was")->plus(UIKit::br())
                ->plus(" originally posted on ")
                ->plus(
                    UIKit::anchor($title, $href)
                );
        }

        if ($details->hasMemberUnfolded("moved")) {
            $copy = $copy->plus(" and moved ")->plus($details->moved);
        }

        return $copy->isNotEmpty(function($result, $string) {
            return ($result->unfold())
                ? UIKit::p($string->plus(".")->unfold())
                : "";
        });
    }

    public function meta(string $type = "website"): WebHead
    {
        return UIKit::webHead()->favicons(
            "/assets/favicons/favicon.ico",
            "/assets/favicons/apple-touch-icon.png",
            "/assets/favicons/favicon-32x32.png",
            "/assets/favicons/favicon-16x16.png"
        )->social(
            $this->handler()->title(ContentHandler::BOOKEND),
            url()->current(),
            $this->handler()->description(),
            $this->handler()->socialImage(),
            $type
        )->socialTwitter(...$this->socialTwitter())
        ->styles(...$this->styles())
        ->scripts(...$this->scripts());
    }

    public function socialTwitter(): ESArray
    {
        return Shoop::array([]);
    }

    public function styles(): ESArray
    {
        return Shoop::array(["/css/main.css"]);
    }

    public function scripts(): ESArray
    {
        return Shoop::array(["/js/main.js"]);
    }

    public function breadcrumbs($homeLinkContent = "", $includeCurrent = false)
    {
        $parts = ContentHandler::uri(true);
        if (! $includeCurrent) {
            $parts = $parts->dropLast();
        }

        return $parts->each(function() use (&$parts) {
            $anchor = UIKit::anchor(
                $this->handler()->title(ContentHandler::HEADING, true, $parts),
                $parts->join("/")->start("/")
            );

            $parts = $parts->dropLast();

            return $anchor;

        })->noEmpties()->isEmpty(
            function($result, $anchors) use ($homeLinkContent, $includeCurrent) {
                $anchors = Shoop::string($homeLinkContent)->isEmpty(
                    function($result, $homeLinkContent) use ($anchors) {
                        return ($result->unfold())
                            ? $anchors
                            : $anchors->plus(
                                UIKit::anchor($homeLinkContent, "/")
                            );
                    });
                return ($result->unfold())
                    ? ""
                    : UIKit::nav(
                        UIKit::listWith(...$anchors)
                    )->attr("class breadcrumbs");
            });
    }

    // TODO: Obviously this needs tests of some kind
    public function tocView($currentPage = 1, $path = "/feed")
    {
        $items = $this->handler()->contentStore(true, $path)->metaMember("toc");
        return UIKit::webView(
            $this->handler()->title(),
            ...$this->toc($currentPage, ...$items)
        )->meta($this->meta());
    }

    // TODO: Move to UIKit
    public function toc($currentPage, $items = [])
    {
        return Type::sanitizeType($items, ESArray::class)
            ->isEmpty(function($result, $items) {
                if ($result->unfold()) { return Shoop::array([]); }

                return $items->each(function($uri) {
                        $parts = Shoop::string($uri)->divide("/", false);
                        return $this->handler()->contentStore(true, ...$parts)
                            ->isFile(function($result, $store) use ($uri, $parts) {
                                if (! $result->unfold()) { return ""; }

                                $title = $this->handler()
                                    ->title(ContentHandler::HEADING, true, $parts);
                                return UIKit::anchor($title, $uri);
                            });
                    })->noEmpties();

            })->isEmpty(function($result, $links) use ($currentPage) {
                if ($result->unfold()) { return Shoop::array([]); }

                return $links->count()->isEmpty(
                        function($result, $totalItems) use ($links, $currentPage) {
                            if ($result->unfold()) { return Shoop::array([]); }

                            if ($totalItems->isGreaterThanUnfolded(10)) {
                                return Shoop::array([
                                    UIKit::listWith(...$links),
                                    UIKit::pagination($currentPage, $totalItems)
                                ]);
                            }

                            return Shoop::array([
                                UIKit::listWith(...$links)
                            ]);
                        });
            });
    }

    public function navNextPrevious()
    {
        return Shoop::array([$this->nextAnchor(), $this->previousAnchor()])
            ->noEmpties()->isEmpty(function($result, $links) {
                if ($result->unfold()) { return ""; }

                return UIKit::nav(...$links)->attr("class next-previous");
            });
    }

    public function nextAnchor()
    {
        // If the content store has a TOC, we always use the first path;
        return $this->handler()->contentStore()->metaMember("toc")->isEmpty(
            function($result, $toc) {
                if ($result->toggle) { return $toc->first(); }
                // if not, try parent;
                return $this->handler()->store()->dropLast()->plus("content.md")
                    ->metaMember("toc")->isEmpty(function($result, $toc) {
                        if ($result->unfold()) { return Shoop::string(""); }

                        return $toc->each(function($path, $index, &$break) use ($toc) {

                            $path = Shoop::string($path);
                            if ($this->handler()->uri()->isUnfolded($path)) {
                                $break = true;
                                return $toc->countIsGreaterThan($index + 1, function($result, $toc) use ($index) {
                                    if ($result->unfold()) { return $toc->get($index + 1); }

                                    return Shoop::string("");
                                });
                            }
                            return Shoop::string("");

                        })->noEmpties()->countIsGreaterThan(0, function($result, $array) {
                            if ($result->unfold()) { return $array->first(); }

                            return Shoop::string("");
                        });
                    });

            })->isEmpty(function($result, $path) {
                if ($result->unfold()) { return Shoop::string(""); }

                $title = $this->handler()->title(
                    ContentHandler::HEADING,
                    true,
                    $path->divide("/", false)
                );
                return UIKit::anchor($title->start("next: "), $path)->attr("class next");
            });
    }

    public function previousAnchor()
    {
        // If the content store has a TOC, we always return empty;
        return $this->handler()->contentStore()->metaMember("toc")->isEmpty(
            function($result, $toc) {
                if ($result->toggle) { return Shoop::string(""); }

                // if not, try parent;
                return $this->handler()->store()->dropLast()->plus("content.md")
                    ->metaMember("toc")->isEmpty(function($result, $toc) {
                        if ($result->unfold()) { return Shoop::string(""); }

                        return $toc->each(function($path, $index, &$break) use ($toc) {
                            $path = Shoop::string($path);
                            $index = Shoop::int($index);
                            if ($this->handler()->uri()->isUnfolded($path)) {
                                $break = true;
                                return $index->isNot(0, function($result, $index) use ($toc) {
                                    if ($result->unfold()) { return $toc->get($index->minusUnfolded(1)); }

                                    return Shoop::string("");
                                });
                            }
                            return Shoop::string("");

                        })->noEmpties()->countIsGreaterThan(0, function($result, $array) {
                            if ($result->unfold()) { return $array->first(); }

                            return $this->handler()->uri(true)->dropLast()
                                ->join("/")->start("/");
                        });
                    });

            })->isEmpty(function($result, $path) {
                if ($result->unfold()) { return Shoop::string(""); }

                $title = $this->handler()->title(
                    ContentHandler::HEADING,
                    true,
                    $path->divide("/", false)
                );
                return UIKit::anchor($title->start("previous: "), $path)->attr("class previous");
            });
        // // Get TOC or root - only in primary categories
        // // find out where I am - the get previous
        // $parts = $this->handler()->uri()->divide("/", false)->countIsLessThan(1, function($result, $parts) {
        //     return ($result->unfold()) ? "" : $parts->first();
        // });

        // if (Shoop::string($parts)->isEmpty) {
        //     return "";
        // }

        // $toc = $this->handler()->contentStore(true, $parts)->metaMember("toc");
        // $previousPath = $toc->each(function($path, $index, &$break) use ($toc) {
        //     $isPathRoot = Shoop::string($path)->divide("/", false)->first()
        //         ->start("/")->isUnfolded($this->handler()->uri());
        //     $isPath = $this->handler()->uri()->isUnfolded($path);
        //     if ($isPathRoot) {
        //         $break = true;
        //         return "";

        //     } elseif ($isPathRoot or $isPath) {
        //         $break = true;
        //         if ($isPathRoot) {
        //             return $toc->first();

        //         } elseif (Shoop::int($index)->isGreaterThanUnfolded(0)) {
        //             return $toc->get($index - 1);

        //         }
        //         return "";
        //     }
        //     return "";

        // })->noEmpties()->reindex()->isEmpty(function($result, $array) {
        //     return ($result->unfold()) ? Shoop::string("") : $array->first();
        // });

        // if ($previousPath->isEmpty) {
        //     return $previousPath;
        // }

        // $title = $this->handler()->title(
        //     ContentHandler::HEADING,
        //     true,
        //     Shoop::string($previousPath)->divide("/", false)
        // )->start("previous: ");

        // if ($title->isEmpty) {
        //     return "";
        // }

        // return UIKit::anchor($title, $previousPath)->attr("class previous");
    }









// -> RSS
    // TODO: Move to ContentHandler
    static public function rssStore()
    {
        return static::rootStore()->plus("feed", "content.md");
    }

    // TODO: Move to ContentHandler
    static public function rssDescriptionReplacements()
    {
        return Shoop::dictionary([
            "</h1>" => ":",
            "</h2>" => ":",
            "</h3>" => ":",
            "</h4>" => ":",
            "</h5>" => ":",
            "</h6>" => ":",
            "<h1>" => "",
            "<h2>" => "",
            "<h3>" => "",
            "<h4>" => "",
            "<h5>" => "",
            "<h6>" => ""
        ]);
    }
}
