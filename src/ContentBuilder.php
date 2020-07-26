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
            AbbreviationExtension::class
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

        if ($this->useSiteTracker) {
            SiteTracker::saveTransaction(
                $this->handler()->trackerStore(),
                session()->getId()
            );
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
        $toc = Shoop::store($path)->divide("/", true)->meta()->toc;
        if ($toc === null) {
            $toc = [];
        }
        return UIKit::webView(
            $this->handler()->title(),
            ...$this->toc(
                $currentPage,
                $this->handler()->store()->plus(...$toc)
        )->meta(...$this->meta());
    }

    // TODO: Move to UIKit
    public function toc($currentPage, $items = [])
    {
        return Type::sanitizeType($items, ESArray::class)
            ->isEmpty(function($result, $items) {
                return ($result->unfold())
                    ? Shoop::array([])
                    : $items->each(function($uri) {
                        $parts = Shoop::string($uri)->divide("/", false);
                        return $this->handler()->contentStore(true, ...$parts)
                            ->isFile(function($result, $store) use ($uri, $parts) {
                                if (! $result->unfold()) {
                                    return "";
                                }
                                $title = $this->handler()
                                    ->title(ContentHandler::HEADING, true, $parts);
                                return UIKit::anchor($title, $uri);
                            });
                    });

            })->isEmpty(function($result, $links) use ($currentPage) {
                return ($result->unfold())
                    ? Shoop::array([])
                    : $links->count()->isEmpty(
                        function($result, $totalItems) use ($links, $currentPage) {
                            return ($result->unfold())
                                ? Shoop::array([])
                                : Shoop::array([
                                    UIKit::listWith(...$links),
                                    UIKit::pagination($currentPage, $totalItems)
                                ]);
                        });
            });
    }

    public function navNextPrevious()
    {
        return Shoop::array([$this->nextAnchor(), $this->previousAnchor()])
            ->noEmpties()->isEmpty(function($result, $links) {
                return ($result->unfold())
                    ? ""
                    : UIKit::nav(...$links)->attr("class next-previous");
            });
    }

    public function nextAnchor()
    {
        // Get TOC or root - only in primary categories
        // find out where I am - the get next
        $parts = $this->handler()->uri()->divide("/", false)->countIsLessThan(1, function($result, $parts) {
            return ($result->unfold()) ? "" : $parts->first();
        });

        if (Shoop::string($parts)->isEmpty) {
            return "";
        }

        $toc = $this->handler()->contentStore(true, $parts)->metaMember("toc");
        $nextPath = $toc->each(function($path, $index, &$break) use ($toc) {
            $isPathRoot = Shoop::string($path)->divide("/", false)->first()
                ->start("/")->isUnfolded($this->handler()->uri());
            $isPath = $this->handler()->uri()->isUnfolded($path);
            if ($isPathRoot or $isPath) {
                $break = true;
                if ($isPathRoot) {
                    return $toc->first();

                } elseif ($toc->countIsGreaterThanUnfolded($index + 1)) {
                    return $toc->get($index + 1);

                }
                return "";
            }
            return "";

        })->noEmpties()->isEmpty(function($result, $array) {
            return ($result->unfold()) ? Shoop::string("") : $array->first();
        });

        if ($nextPath->isEmpty) {
            return $nextPath;
        }

        $title = $this->handler()->title(
            ContentHandler::HEADING,
            true,
            Shoop::string($nextPath)->divide("/", false)
        )->start("next: ");

        if ($title->isEmpty) {
            return "";
        }

        return UIKit::anchor($title, $nextPath)->attr("class next");
    }

    public function previousAnchor()
    {
        // Get TOC or root - only in primary categories
        // find out where I am - the get previous
        $parts = $this->handler()->uri()->divide("/", false)->countIsLessThan(1, function($result, $parts) {
            return ($result->unfold()) ? "" : $parts->first();
        });

        if (Shoop::string($parts)->isEmpty) {
            return "";
        }

        $toc = $this->handler()->contentStore(true, $parts)->metaMember("toc");
        $previousPath = $toc->each(function($path, $index, &$break) use ($toc) {
            $isPathRoot = Shoop::string($path)->divide("/", false)->first()
                ->start("/")->isUnfolded($this->handler()->uri());
            $isPath = $this->handler()->uri()->isUnfolded($path);
            if ($isPathRoot) {
                $break = true;
                return "";

            } elseif ($isPathRoot or $isPath) {
                $break = true;
                if ($isPathRoot) {
                    return $toc->first();

                } elseif (Shoop::int($index)->isGreaterThanUnfolded(0)) {
                    return $toc->get($index - 1);

                }
                return "";
            }
            return "";

        })->noEmpties()->reindex()->isEmpty(function($result, $array) {
            return ($result->unfold()) ? Shoop::string("") : $array->first();
        });

        if ($previousPath->isEmpty) {
            return $previousPath;
        }

        $title = $this->handler()->title(
            ContentHandler::HEADING,
            true,
            Shoop::string($previousPath)->divide("/", false)
        )->start("previous: ");

        if ($title->isEmpty) {
            return "";
        }

        return UIKit::anchor($title, $previousPath)->attr("class previous");
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
