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

    static public function eventStore()
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

    static public function uri(): ESString
    {
        return Shoop::string(request()->path())->start("/");
    }

    static public function uriRoot(): ESString
    {
        return static::uriParts()->isEmpty(function($result, $array) {
            return ($result) ? Shoop::string("") : $array->first();
        });
    }

    static public function uriParts(): ESArray
    {
        return static::uri()->divide("/")->noEmpties()->reindex();
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

    static public function markdownConfig()
    {
        return Shoop::dictionary([
            "html_input" => "strip",
            "allow_unsafe_links" => false
        ])->plus(Shoop::dictionary(["open_in_new_window" => true]), "external_link")
        ->plus(Shoop::dictionary(["symbol" => "#"]), "heading_permalink")
        ->unfold();
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

    /**
     * @return ESStore An ESStore where the path goes to the root of the content folder.
     */
    static public function contentStore(): ESStore
    {
        return Shoop::store(__DIR__)->plus("Content");
    }

    /**
     * The `/.assets` folder can contain whatever you like, but should contain the favicons if you use the `faviconPack()` method and routes.
     *
     * @return ESStore An ESStore where the path goes to a hidden subfolder of the root content folder.
     */
    static public function assetsStore(): ESStore
    {
        return static::contentStore()->plus(".assets");
    }

    /**
     * The `/.media` folder can contain whatever you like, but should contain images if you use the `media` routes.
     *
     * @return ESStore An ESStore where the path goes to a hidden subfolder of the root content folder.
     */
    static public function mediaStore()
    {
        return static::contentStore()->plus(".media");
    }

    static public function meta()
    {
        return Shoop::array([
            UIKit::meta()->attr(
                "name viewport",
                "content width=device-width,
                initial-scale=1"
            ),
            UIKit::link()->attr(
                "type image/x-icon",
                "rel icon",
                "href /assets/favicons/favicon.ico"
            ),
            UIKit::link()->attr(
                "rel apple-touch-icon",
                "href /assets/favicons/apple-touch-icon.png",
                "sizes 180x180"
            ),
            UIKit::link()->attr(
                "rel image/png",
                "href /assets/favicons/favicon-32x32.png",
                "sizes 32x32"
            ),
            UIKit::link()->attr(
                "rel image/png",
                "href /assets/favicons/favicon-16x16.png",
                "sizes 16x16"
            )
        ]);
    }

    static public function copyright($holder = "")
    {
        return Shoop::string("Copyright © {$holder} ". date("Y") .". All rights reserved.");
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
