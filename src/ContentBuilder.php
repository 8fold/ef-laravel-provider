<?php

namespace Eightfold\Site;

use Carbon\Carbon;

use Spatie\YamlFrontMatter\YamlFrontMatter;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\DocParser;
use League\CommonMark\Environment;
use League\CommonMark\HtmlRenderer;

// available extensions
use League\CommonMark\Extension\{
    GithubFlavoredMarkdownExtension,
    Autolink\AutolinkExtension,
    DisallowedRawHtml\DisallowedRawHtmlExtension,
    Strikethrough\StrikethroughExtension,
    Table\TableExtension,
    TaskList\TaskListExtension
};

use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkRenderer;
use League\CommonMark\Extension\InlinesOnly\InlinesOnlyExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use League\CommonMark\Extension\SmartPunct\SmartPunctExtension;

use Eightfold\CommonMarkAbbreviations\AbbreviationExtension;
// end extensions
//
use Eightfold\ShoopExtras\{
    Shoop,
    ESStore
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

abstract class ContentBuilder
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

// -> Content
    static public function titles($checkHeadingFirst = true): ESArray
    {
        $store = static::store();
        if (static::rootUri()->isUnfolded("events")) {
            $store = static::eventStore();
        }

        return $store->plus("content.md")->isFile(
            function($result, $store) use ($checkHeadingFirst) {
                if (! $result) { return Shoop::array([]); }

                $parts = Shoop::path(request()->path())->parts();
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
                        $s->metaMember("headding")->isNotEmpty
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
                    static::rootStore()->plus("content.md")->metaMember("title")->unfold()
                );
        });
    }

    static public function title($type = "", $checkHeadingFirst = true): ESString
    {
        if (strlen($type) === 0) {
            $type = static::PAGE;
        }

        $titles = Shoop::array([]);
        if ($checkHeadingFirst and
            Shoop::string(static::HEADING)->isUnfolded($type)
        ) {
            $titles = $titles->plus(
                static::titles($checkHeadingFirst)->first()
            );

        } elseif (Shoop::string(static::TITLE)->isUnfolded($type)) {
            $titles = $titles->plus(
                static::titles(false)->first()
            );

        } elseif (Shoop::string(static::BOOKEND)->isUnfolded($type)) {
            $t = static::titles($checkHeadingFirst)->divide(-1);
            $start = $t->first();
            $root = $t->last();
            if (static::uriRoot()->isUnfolded("events")) {
                $eventTitles = static::eventsTitles();
                $start = $start->start($eventTitles->month ." ". $eventTitles->year);
            }

            if ($root->count()->isGreaterThanUnfolded(1)) {
                $root = $titles->plus($root->last());
            }

            $titles = $titles->plus(...$start)->plus(...$root)->noEmpties();
            $titles = Shoop::array([
                $titles->first(),
                $titles->last()
            ]);

        } elseif (Shoop::string(static::PAGE)->isUnfolded($type)) {
            $t = static::titles($checkHeadingFirst)->divide(-1);
            $start = $t->first();
            $root = $t->last();
            if (static::uriRoot()->isUnfolded("events")) {
                $eventTitles = static::eventsTitles();
                $start = $start->start($eventTitles->month, $eventTitles->year);
            }
            $titles = $titles->plus(...$start)->plus(...$root)->noEmpties();
        }
        return $titles->noEmpties()->join(" | ");
    }

    static public function eventsTitles($type = "")
    {
        $parts = Shoop::string(request()->path())->divide("/");
        $year = $parts->dropFirst()->first;
        $month = Carbon::createFromFormat(
            "m",
            $parts->dropFirst(2)->first,
            "America/Chicago"
        )->format("F");

        return Shoop::dictionary([
            "year"  => $year,
            "month" => $month
        ]);
    }

    static public function copyright($name, $startYear = ""): ESString
    {
        if (strlen($startYear) > 0) {
            $startYear = $startYear ."&ndash;";
        }
        return Shoop::string("Copyright © {$startYear}". date("Y") ." {$name}. All rights reserved.");
    }

// -> UI
    static public function meta(): ESArray
    {
        return Shoop::array([
            UIKit::meta()->attr(
                "name viewport",
                "content width=device-width,
                initial-scale=1"
            )
        ])->plus(...static::metaFavicons())
        ->plus(...static::metaShare())
        ->plus(...static::metaStyles())
        ->plus(...static::metaScripts());
    }

    static public function faviconsMeta()
    {
        return Shoop::array([
            UIKit::link()->attr("type image/x-icon", "rel icon", "href /assets/favicons/favicon.ico"),
            UIKit::link()->attr("rel apple-touch-icon", "href /assets/favicons/apple-touch-icon.png", "sizes 180x180"),
            UIKit::link()->attr("rel image/png", "href /assets/favicons/favicon-32x32.png", "sizes 32x32"),
            UIKit::link()->attr("rel image/png", "href /assets/favicons/favicon-16x16.png", "sizes 16x16")
        ]);
    }

    static public function stylesMeta()
    {
        return Shoop::array([
            UIKit::link()->attr("rel stylesheet", "href /css/main.css")
        ]);
    }

    static public function scriptsMeta()
    {
        return Shoop::array([
            UIKit::script()->attr("src /js/main.js")
        ]);
    }

    static public function shareMeta()
    {
        return Shoop::array([
            UIKit::meta()->attr("property og:title", "content ". static::title(static::BOOKEND)),
            UIKit::meta()->attr("property og:url", "content ". url()->current()),
            UIKit::meta()->attr("property og:image", "content ". static::shareImage())
        ]);
    }

    /**
     * @deprecated
     */
    static public function metaFavicons()
    {
        return static::faviconsMeta();
    }

    /**
     * @deprecated
     */
    static public function metaStyles()
    {
        return static::stylesMeta();
    }

    /**
     * @deprecated
     */
    static public function metaScripts()
    {
        return static::scriptsMeta();
    }

    /**
     * @deprecated
     */
    static public function metaShare()
    {
        return static::shareMeta();
    }

    abstract static public function shareImage(): ESString;

    static public function markdownConfig()
    {
        return Shoop::dictionary([
            "html_input" => "strip",
            "allow_unsafe_links" => false
        ])->plus(Shoop::dictionary(["open_in_new_window" => true]), "external_link")
        ->plus(Shoop::dictionary(["symbol" => "#"]), "heading_permalink")
        ->unfold();
    }

// -> URI
    static public function uri($parts = false) // :ESString|ESArray
    {
        $base = Shoop::string(request()->path())->start("/");
        if ($parts) {
            return $base->divide("/")->noEmpties()->reindex();
        }
        return $base;
    }

    static public function rootUri(): ESString
    {
        return static::uriParts()->isEmpty(function($result, $array) {
            return ($result) ? Shoop::string("") : $array->first();
        });
    }

    /**
     * @deprecated
     */
    static public function uriParts(): ESArray
    {
        return static::uri(true);
    }

    /**
     * @deprecated
     */
    static public function uriRoot(): ESString
    {
        return static::rootUri();
    }

// -> Stores
    abstract static public function rootStore(): ESStore;

    static public function store(...$plus): ESStore
    {
        if (Shoop::string(request()->path())->isUnfolded("/")) {
            return static::rootStore();
        }
        $parts = Shoop::path(request()->path())->parts();
        return static::rootStore()->plus(...$parts)->plus(...$plus);
    }

    static public function assetsStore(): ESStore
    {
        return static::rootStore()->plus(".assets");
    }

    static public function eventStore(): ESStore
    {
        return static::rootStore()->plus("events");
    }


















    static public function view(...$content)
    {
        return UIKit::webView(
            static::uriPageTitle(),
            ...Shoop::array($content)->isEmpty(function($result, $content) {
                return ($result)
                    ? Shoop::array(static::uriContentMarkdownHtml())
                    : $content;
            })
        )->meta(...static::meta());
    }

    static public function uriTocView(
        $currentPage = 1,
        $path = "/feed"
    )
    {
        return UIKit::webView(
            static::uriPageTitle(),
            static::uriContentMarkdownHtml(false, [], [], true, true, [], $path),
            ...static::uriToc($currentPage, static::uriContentMarkdown($path)->meta()->toc())
        )->meta(...static::meta());
    }


    static public function uriContentStore($uri = ""): ESStore
    {
        $uri = Type::sanitizeType($uri, ESString::class)
            ->isEmpty(function($result, $uri) {
                return ($result) ? static::uriParts() : $uri->divide("/")->noEmpties();
            });
        return static::contentStore()->plus(...$uri)->plus("content.md");
    }

    static public function uriContentMarkdown($uri = "")
    {
        return static::uriContentStore($uri)->markdown()->extensions(
            ...static::uriContentMarkdownExtensions()
        );
    }

    static public function uriContentMarkdownExtensions()
    {
        return Shoop::array([
            StrikethroughExtension::class,
            TableExtension::class,
            TaskListExtension::class,
            ExternalLinkExtension::class,
            SmartPunctExtension::class,
            // AbbreviationExtension::class,
            HeadingPermalinkExtension::class
        ]);
    }

    static public function uriContentMarkdownDetails()
    {
        $markdown = static::uriContentMarkdown();

        $modified = ($markdown->meta()->modified === null)
            ? Shoop::string("")
            : Shoop::string("Modified on: ")->plus(
                    Carbon::createFromFormat("Ymd", $markdown->meta()->modified, "America/Chicago")
                        ->toFormattedDateString()
                );

        $created = ($markdown->meta()->created === null)
            ? Shoop::string("")
            : Shoop::string("Created on: ")->plus(
                    Carbon::createFromFormat("Ymd", $markdown->meta()->created, "America/Chicago")
                        ->toFormattedDateString()
                );

        $moved = ($markdown->meta()->moved === null)
            ? Shoop::string("")
            : Shoop::string("Moved on: ")->plus(
                    Carbon::createFromFormat("Ymd", $markdown->meta()->moved, "America/Chicago")
                        ->toFormattedDateString()
                );

        return Shoop::array([$modified, $created, $moved])->noEmpties();
    }

    static public function uriContentMarkdownDetailsParagraph()
    {
        return self::uriContentMarkdownDetails()->count()
            ->is(0, function($result) {
                return ($result)
                    ? Shoop::string("")
                    : UIKit::p(
                        self::uriContentMarkdownDetails()->join(UIKit::br())->unfold()
                    );
            });
    }

    static public function uriToc($currentPage, $items = [])
    {
        return Type::sanitizeType($items, ESArray::class)
            ->isEmpty(function($result, $items) {
                return ($result)
                    ? Shoop::array([])
                    : $items->each(function($uri) {
                        return static::uriContentStore($uri)
                            ->isFile(function($result, $store) use ($uri) {
                                if (! $result) {
                                    return "";
                                }
                                $title = static::uriTitleForContentStore($store);
                                return UIKit::anchor($title, $uri);
                            });
                    });

            })->isEmpty(function($result, $links) use ($currentPage) {
                return ($result)
                    ? Shoop::array([])
                    : $links->count()->isGreaterThan(0,
                        function($result, $totalItems) use ($links, $currentPage) {
                            return (! $result)
                                ? Shoop::array([])
                                : Shoop::array([
                                    UIKit::listWith(...$links),
                                    UIKit::pagination($currentPage, $totalItems)
                                ]);
                        });
            });
    }

// TODO: Test
    static public function uriBreadcrumbs()
    {
        $store = static::uriContentStore()->parent();
        $uri = static::uriParts();
        return static::uriParts()->each(function($part) use (&$store, &$uri) {
            $title = static::uriTitleForContentStore($store);
            $href = $uri->join("/")->start("/");
            $anchor = UIKit::anchor($title, $href);

            $store = $store->parent();
            $uri = $uri->dropLast();

            return $anchor;
        })->noEmpties()->dropFirst()->isEmpty(function($result, $paths) {
            return ($result)
                ? ""
                : UIKit::nav(
                    UIKit::listWith(...$paths)
                )->attr("class breadcrumbs");
        });
    }

    static public function uriContentMarkdownHtml(
        $details = true,
        $markdownReplacements = [],
        $htmlReplacements = [],
        $caseSensitive = true,
        $minified = true,
        $config = [],
        $uri = ""
    )
    {
        $details = Type::sanitizeType($details, ESBool::class)->unfold();

        $config = (empty($config)) ? static::markdownConfig() : $config;

        $markdown = static::uriContentMarkdown($uri);
        if ($markdown->isEmpty) {
            abort(404);
        }

        $html = $markdown->html(
            $markdownReplacements,
            $htmlReplacements,
            $caseSensitive,
            $minified,
            $config
        );


        if ($details) {
            $title = UIKit::h1(static::uriTitleForContentStore(static::uriContentStore($uri)));

            $details = static::uriContentMarkdownDetails();
            if (Type::is($details, ESArray::class)) {
                return $html->start($title->unfold(), ...$details);
            }
            return $html->start($title->unfold(), $details->unfold());
        }
        return $html;
    }

    static public function uriTitleForContentStore(ESStore $store)
    {
        return $store->isFolder(function($result, $store) {
            if ($result) {
                return $store->plus("content.md")->isFile(function($result, $store) {
                    if ($result) {
                        return ($store->markdown()->meta()->heading === null)
                            ? $store->markdown()->meta()->title
                            : $store->markdown()->meta()->heading;
                    }
                    return "";
                });
            }
            return "";
        });
    }

    static public function uriPageTitle(): ESString
    {
        $store = static::uriContentStore()->parent();
        return Shoop::string(static::uri())->divide("/")->each(function($part) use (&$store) {
            $title = static::uriTitleForContentStore($store);
            $store = $store->parent();
            return $title;
        })->noEmpties()->join(" | ");
    }

    static public function uriShareTitle(): ESString
    {
        $store = static::uriContentStore()->parent();
        $titles = Shoop::string(static::uri())->divide("/")->each(function($part) use (&$store) {
            $title = static::uriTitleForContentStore($store);
            $store = $store->parent();
            return $title;
        })->noEmpties();
        return Shoop::array([$titles->last])->plus($titles->first)
            ->toggle()->join(" | ");
    }

    /**
     * @deprecated
     */
    static public function contentList($page = 1, $limit = 10)
    {
        if (static::rssItemsStoreItems() === null) {
            return Shoop::string("");
        }
        $items = static::rssItemsStoreItems();
        $links = $items->divide(($page - 1) * $limit)
            ->last()->first(10)->isEmpty(function($result, $items) {
                return ($result)
                    ? Shoop::string("")
                    : $items->each(function($uri) {
                        $title = (static::uriContentStore($uri)->markdown()
                            ->meta()->heading === null)
                            ? static::uriContentStore($uri)->markdown()
                                ->meta()->title
                            : static::uriContentStore($uri)->markdown()
                                ->meta()->heading;
                        return ($title === null)
                            ? Shoop::string("")
                            : UIKit::anchor($title, $uri);
                    });
            })->noEmpties();
        $list = UIKit::listWith(...$links);
        $nav = $items->count()->isGreaterThan($limit, function($result, $count) use ($limit) {
            $pageLinks = $count->roundUp($limit)->range(1)->each(function($pageNumber) {
                return UIKit::anchor($pageNumber, "/feed/page/{$pageNumber}");
            });

            if ($pageLinks->count()->isUnfolded(1)) {
                return "";

            } elseif ($pageLinks->count()->isGreaterThanUnfolded(2)) {
                die("build next previous links and first and last pages");
                return UIKit::listWith(...$pageLinks);

            } else {
                return UIKit::nav(UIKit::listWith(...$pageLinks));
            }
        });
        return Shoop::array([
            $list,
            $nav
        ]);
    }

// -> RSS
    static public function rssCompiled()
    {
        return Shoop::string(
            Element::fold("rss",
                Element::fold("channel",
                    Element::fold("title", static::rssTitle()),
                    Element::fold("link", static::rssLink()),
                    Element::fold("description", static::rssDescription()),
                    Element::fold("language", "en-us"),
                    Element::fold("copyright", static::copyright()->unfold()),
                    ...static::rssItemsStoreItems()->each(function($path) {
                        $markdown = static::uriContentStore($path)->markdown();

                        $title = $markdown->meta()->title;
                        $link = Shoop::string(static::rssLink())
                            ->plus($path)->unfold();

                        $description = ($markdown->meta()->description === null)
                            ? $markdown->html()
                            : $markdown->meta()->description();
                        if ($description->count()->isUnfolded(0)) {
                            return "";
                        }
                        $description = $description->replace(static::rssDescriptionReplacements())
                            ->dropTags()->divide(" ")->isGreaterThan(50, function($result, $description) {
                            return ($result)
                                ? $description->first(50)->join(" ")->plus("...")
                                : $description->join(" ");
                        });

                        $timestamp = ($markdown->meta()->created === null)
                            ? ""
                            : Carbon::createFromFormat("Ymd", $markdown->meta()->created, "America/Detroit")
                                ->hour(12)
                                ->minute(0)
                                ->second(0)
                                ->toRssString();
                        $t = (strlen($timestamp) > 0)
                            ? Element::fold("pubDate", $timestamp)
                            : "";

                        $item = Element::fold(
                                "item",
                                    Element::fold("title", htmlspecialchars($title)),
                                    Element::fold("link", $link),
                                    Element::fold("guid", $link),
                                    Element::fold("description", htmlspecialchars($description)),
                                    $t
                            );
                        return $item;
                    })->noEmpties()
                )
            )->attr("version 2.0")->unfold()
        )->start("<?xml version=\"1.0\"?>\n");
    }

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

    static public function rssItemsStore()
    {
        return static::contentStore()->plus("feed", "content.md");
    }

    static public function rssTitle()
    {
        return static::rssItemsStore()->markdown()->meta()->rssTitle;
    }

    static public function rssLink()
    {
        return static::rssItemsStore()->markdown()->meta()->rssLink;
    }

    static public function rssDescription()
    {
        return static::rssItemsStore()->markdown()->meta()->rssDescription;
    }

    static public function rssItemsStoreItems()
    {
        if (static::rssItemsStore()->markdown()->meta()->toc === null) {
            return Shoop::array([]);
        }
        return static::rssItemsStore()->markdown()->meta()->toc();
    }
}
