<?php

namespace Eightfold\Site;

use Carbon\Carbon;

use Spatie\YamlFrontMatter\YamlFrontMatter;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\DocParser;
use League\CommonMark\Environment;
use League\CommonMark\HtmlRenderer;
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

use Eightfold\ShoopExtras\{
    Shoop,
    ESStore
};

use Eightfold\Shoop\Helpers\Type;

use Eightfold\Shoop\{
    ESArray,
    ESString
};

use Eightfold\Markup\UIKit;
use Eightfold\Markup\Element;

abstract class ContentBuilder
{
    abstract static public function view(...$content);

    static public function uri(): ESString
    {
        return Shoop::string(request()->path())->start("/");
    }

    static public function uriRoot(): ESString
    {
        return static::uriParts()->first();
    }

    static public function uriParts(): ESArray
    {
        return static::uri()->divide("/")->noEmpties()->reindex();
    }

    static public function uriContentStore(): ESStore
    {
        return static::contentStore()->plus(...static::uriParts())->plus("content.md");
    }

    static public function uriContentMarkdown()
    {
        return static::uriContentStore()->markdown()->extensions(
            GithubFlavoredMarkdownExtension::class,
            ExternalLinkExtension::class,
            SmartPunctExtension::class
        );
    }

    static public function markdownConfig()
    {
        return [
            "html_input" => "strip",
            "allow_unsafe_links" => false,
            "external_link" => [
                "open_in_new_window" => true
            ]
        ];
    }

    static public function uriContentMarkdownHtml()
    {
        return static::uriContentMarkdown()->html(
            [], [], true, true, static::markdownConfig()
        );
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

    /**
     * @return ESStore An ESStore where the path goes to the root of the content folder.
     */
    abstract static public function contentStore(): ESStore;

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

// -> RSS
    static public function descriptionForPathParts(ESArray $pathParts): string
    {
        return static::markdownForPathParts($pathParts)->isEmpty(function($result, $value) {
            if ($result) {
                return Shoop::string("");
            }

            $markdown = Shoop::markdown($value);
            $description = $markdown->meta()->description;
            if ($description === null) {
                $description = $markdown->html();

            } else {
                $description = Shoop::string($description);

            }
            return $description->replace(static::rssReplacements())
                ->dropTags()->divide(" ")->first(50)->join(" ")->plus("...");
        });
    }

    static public function rssReplacements()
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

    static public function rssItemForPath(string $path)
    {
        $cp = static::contentPath($path)->divide("/");
        $title = static::titleForPathParts($cp);
        $link = "https://". static::domain() . $path;
        $description = static::descriptionForPathParts($cp);
        $timestamp = static::markdownForPathParts($cp)->meta()
            ->isEmpty(function($result, $value) {
                if ($result or $value->published_on()->isEmptyUnfolded()) {
                    return "";
                }
                return Carbon::createFromFormat("Ymd", $value->published_on, "America/Detroit")
                    ->toRssString();
            });
        return Element::fold(
                "item",
                    Element::fold("title", htmlspecialchars($title)),
                    Element::fold("link", $link),
                    Element::fold("guid", $link),
                    Element::fold("description", htmlspecialchars($description)),
                    Element::fold("pubDate", $timestamp)
            );
    }
}
