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
    static public function titles($checkHeadingFirst = true, $parts = []): ESArray
    {
        $parts = Type::sanitizeType($parts, ESArray::class);

        $store = static::store();
        if ($parts->isNotEmpty) {
            $store = static::rootStore()->plus(...$parts);

        }

        if (static::rootUri()->isUnfolded("events")) {
            $store = static::eventStore();
        }
        return $store->plus("content.md")->isNotFile(
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
                    static::rootStore()->plus("content.md")->metaMember("title")->unfold()
                );
        });
    }

    static public function title($type = "", $checkHeadingFirst = true, $parts = []): ESString
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
                static::titles($checkHeadingFirst, $parts)->first()
            );

        } elseif (Shoop::string(static::TITLE)->isUnfolded($type)) {
            $titles = $titles->plus(
                static::titles(false, $parts)->first()
            );

        } elseif (Shoop::string(static::BOOKEND)->isUnfolded($type)) {
            $t = static::titles($checkHeadingFirst, $parts)->divide(-1);
            $start = $t->first();
            if ($t->countIsGreaterThanUnfolded(1)) {
                $root = $t->last();
            }

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
            $t = static::titles($checkHeadingFirst, $parts)->divide(-1);
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
    static public function view(...$extras)
    {
        if (static::store("content.md")->isNotFile) {
            abort(404);
        }

        return UIKit::webView(
                static::title(),
                UIKit::main(
                    static::markdown(),
                    ...$extras
                ),
                static::footer()
            )->meta(...static::meta());
    }

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
        // https://developers.facebook.com/tools/debug/?q=https%3A%2F%2Fliberatedelephant.com%2F
        // https://cards-dev.twitter.com/validator
        return Shoop::array([
            UIKit::meta()->attr("property og:title", "content ". static::title(static::BOOKEND)),
            UIKit::meta()->attr("property og:url", "content ". url()->current()),
            UIKit::meta()->attr("property og:image", "content ". static::shareImage()),

            // LinkedIn required the description tag
            UIKit::meta()->attr("property og:description", "content ". static::shareDescription()),

            // recommended adding the following to you own implementation, and
            // specifying the proper dimensions as these represent the minimum
            // required by Open Graph
            // UIKit::meta()->attr("property og:image:width", "content 1200+")
            // UIKit::meta()->attr("property og:image:height", "content 630+")
        ])->plus(...static::shareTwitter());
    }

    static public function breadcrumbs()
    {
        $uri = static::uri(true)->dropLast();
        return $uri->each(function($part) use (&$uri) {
            $anchor = UIKit::anchor(
                static::title(static::HEADING, true, $uri),
                $uri->join("/")->start("/")
            );

            $uri = $uri->dropLast();

            return $anchor;

        })->noEmpties()->isEmpty(function($result, $anchors) {
            return ($result->unfold())
                ? ""
                : UIKit::nav(
                    UIKit::listWith(...$anchors)
                )->attr("class breadcrumbs");
        });
    }

    static public function contentDetailsView()
    {
        $details = static::contentDetails();

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

        return $copy->isEmpty(function($result, $string) {
            return ($result->unfold())
                ? ""
                : UIKit::p($string->plus(".")->unfold());
        });
    }

    abstract static public function shareImage(): ESString;

    static public function shareDescription(): ESString
    {
        return static::title(static::BOOKEND);
    }

    static public function shareTwitter(): ESSArray
    {
        return Shoop::array([]);
    }

    static public function tocView($currentPage = 1, $path = "/feed")
    {
        return UIKit::webView(
            static::title(),
            ...static::toc($currentPage, static::store()->plus(...Shoop::string($path)->divide("/", true))->meta()->toc())
        )->meta(...static::meta());
    }

    static public function toc($currentPage, $items = [])
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

    static public function markdownConfig()
    {
        return Shoop::dictionary([
            "html_input" => "strip",
            "allow_unsafe_links" => false
        ])->plus(Shoop::dictionary(["open_in_new_window" => true]), "external_link")
        ->plus(Shoop::dictionary(["symbol" => "#"]), "heading_permalink")
        ->unfold();
    }

// -> Content
    static public function contentDetails()
    {
        $meta = static::store()->plus("content.md")->markdown()->meta();

        $return = Shoop::dictionary([]);
        $return = $return->plus(
            ($meta->created === null)
                ? Shoop::string("")
                : Shoop::string(
                        Carbon::createFromFormat("Ymd", $meta->created, "America/Chicago")
                            ->toFormattedDateString()
                ),
            "created"
        );

        $return = $return->plus(
            ($meta->modified === null)
                ? Shoop::string("")
                : Shoop::string(
                    Carbon::createFromFormat("Ymd", $meta->modified, "America/Chicago")
                        ->toFormattedDateString()
                ),
            "modified"
        );

        $return = $return->plus(
            ($meta->moved === null)
                ? Shoop::string("")
                : Shoop::string(
                        Carbon::createFromFormat("Ymd", $meta->moved, "America/Chicago")
                            ->toFormattedDateString()
                    ),
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

    static public function mediaStore(): ESStore
    {
        return static::rootStore()->plus(".media");
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
    static public function markdown()
    {
        return UIKit::markdown(
            static::store("content.md")->markdown()->content()->unfold()
        )->prepend("# ". static::title(static::HEADING) ."\n\n". static::contentDetailsView() ."\n\n")
        ->extensions(...static::markdownExtensions());
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
