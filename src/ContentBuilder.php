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
                if (! $result->unfold()) { return Shoop::array([]); }

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
            if (static::rootUri()->isUnfolded("events")) {
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
            if (static::rootUri()->isUnfolded("events")) {
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
        ])->plus(...static::faviconsMeta())
        ->plus(...static::shareMeta())
        ->plus(...static::stylesMeta())
        ->plus(...static::scriptsMeta());
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
        $base = Shoop::uri(request()->url())->tail();
        if ($parts) {
            return $base->divide("/")->noEmpties()->reindex();
        }
        return $base;
    }

    static public function rootUri(): ESString
    {
        return static::uri(true)->isEmpty(function($result, $array) {
            return ($result->unfold()) ? Shoop::string("") : $array->first();
        });
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

// -> RSS
    static public function rssStore()
    {
        return static::rootStore()->plus("feed", "content.md");
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

// -> Markdown
    static public function markdown($uri = "")
    {
        return Shoop::string($uri)->divide("/", false)->countIsGreaterThan(0,
            function($result, $parts) {
                $store = static::store();
                if ($result->unfold()) {
                    $store = static::store(...$parts);
                }
                return $store->plus("content.md")->extensions(
                    ...static::markdownExtensions()
                );
        });
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

// -> Deprecated



























    static public function view(...$content)
    {
        return UIKit::webView(
            static::uriPageTitle(),
            ...Shoop::array($content)->isEmpty(function($result, $content) {
                return ($result->unfold())
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



    // static public function uriContentMarkdownDetails()
    // {
    //     $markdown = static::uriContentMarkdown();

    //     $modified = ($markdown->meta()->modified === null)
    //         ? Shoop::string("")
    //         : Shoop::string("Modified on: ")->plus(
    //                 Carbon::createFromFormat("Ymd", $markdown->meta()->modified, "America/Chicago")
    //                     ->toFormattedDateString()
    //             );

    //     $created = ($markdown->meta()->created === null)
    //         ? Shoop::string("")
    //         : Shoop::string("Created on: ")->plus(
    //                 Carbon::createFromFormat("Ymd", $markdown->meta()->created, "America/Chicago")
    //                     ->toFormattedDateString()
    //             );

    //     $moved = ($markdown->meta()->moved === null)
    //         ? Shoop::string("")
    //         : Shoop::string("Moved on: ")->plus(
    //                 Carbon::createFromFormat("Ymd", $markdown->meta()->moved, "America/Chicago")
    //                     ->toFormattedDateString()
    //             );

    //     return Shoop::array([$modified, $created, $moved])->noEmpties();
    // }

    static public function uriContentMarkdownDetailsParagraph()
    {
        return self::uriContentMarkdownDetails()->count()
            ->is(0, function($result) {
                return ($result->unfold())
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
                return ($result->unfold())
                    ? Shoop::array([])
                    : $items->each(function($uri) {
                        return static::uriContentStore($uri)
                            ->isFile(function($result, $store) use ($uri) {
                                if (! $result->unfold()) {
                                    return "";
                                }
                                $title = static::uriTitleForContentStore($store);
                                return UIKit::anchor($title, $uri);
                            });
                    });

            })->isEmpty(function($result, $links) use ($currentPage) {
                return ($result->unfold())
                    ? Shoop::array([])
                    : $links->count()->isGreaterThan(0,
                        function($result, $totalItems) use ($links, $currentPage) {
                            return (! $result->unfold())
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
        $store = static::store("content.md")->parent();
        $uri = static::uriParts();
        return static::uriParts()->each(function($part) use (&$store, &$uri) {
            $title = static::uriTitleForContentStore($store);
            $href = $uri->join("/")->start("/");
            $anchor = UIKit::anchor($title, $href);

            $store = $store->parent();
            $uri = $uri->dropLast();

            return $anchor;
        })->noEmpties()->dropFirst()->isEmpty(function($result, $paths) {
            return ($result->unfold())
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
}
