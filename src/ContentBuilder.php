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
    ESString
};

use Eightfold\Markup\UIKit;
use Eightfold\Markup\Element;

abstract class ContentBuilder
{
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

    static public function uriContentMarkdown()
    {
        return static::uriContentStore()->markdown()->extensions(
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

        return Shoop::array([$modified, $created])->noEmpties()->count()
            ->isEmpty(function($result) use ($modified, $created) {
                return ($result)
                    ? Shoop::string("")
                    : UIKit::p(
                        Shoop::array([$modified, $created])->noEmpties()
                            ->join(UIKit::br())->unfold()
                    );
            });
    }

// TODO: Test
    static public function uriBreadcrumbs()
    {
        $store = static::uriContentStore()->parent();
        $uri = static::uriParts();
        $paths = static::uriParts()->each(function($part) use (&$store, &$uri) {
            $title = $store->plus("content.md")->markdown()->meta()->title;
            $href = $uri->join("/")->start("/");
            $anchor = UIKit::anchor($title, $href);

            $store = $store->parent();
            $uri = $uri->dropLast();

            return $anchor;
        })->noEmpties()->dropFirst();

        return UIKit::nav(
                UIKit::listWith(...$paths)
            )->attr("class breadcrumbs");
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
        $config = []
    )
    {
        $details = Type::sanitizeType($details, ESBool::class)->unfold();

        $config = (empty($config)) ? static::markdownConfig() : $config;

        $html = static::uriContentMarkdown()->html(
            $markdownReplacements,
            $htmlReplacements,
            $caseSensitive,
            $minified,
            $config
        );

        if ($details) {
            $markdown = static::uriContentMarkdown();

            $title = ($markdown->meta()->heading === null)
                ? UIKit::h1(Shoop::string($markdown->meta()->title)->unfold())
                : UIKit::h1(Shoop::string($markdown->meta()->heading)->unfold());

            $details = static::uriContentMarkdownDetails();
            return $html->start($title->unfold(), $details->unfold());
        }
        return $html;
    }


    static public function uriPageTitle(): ESString
    {
        $store = static::uriContentStore()->parent();
        return Shoop::string(static::uri())->divide("/")->each(function($part) use (&$store) {
            $title = $store->plus("content.md")->markdown()->meta()->title;
            $store = $store->parent();
            return $title;
        })->noEmpties()->join(" | ");
    }

    static public function uriShareTitle(): ESString
    {
        $store = static::uriContentStore()->parent();
        $titles = Shoop::string(static::uri())->divide("/")->each(function($part) use (&$store) {
            $title = $store->plus("content.md")->markdown()->meta()->title;
            $store = $store->parent();
            return $title;
        })->noEmpties();
        return Shoop::array([$titles->last])->plus($titles->first)
            ->toggle()->join(" | ");
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
        return static::rssItemsStore()->markdown()->meta()->rssItems();
    }
}
