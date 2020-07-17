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

use Eightfold\Site\ContentHandler;

abstract class ContentBuilder
{
    abstract public function socialImage(): ESString;

    static public function fold(ESPath $localRootPath, ESPath $remoteRootPath = null)
    {
        return new static($localRootPath, $remoteRootPath);
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

    static public function socialType()
    {
        return "website";
    }

    static public function socialTwitter(): ESArray
    {
        return Shoop::array([]);
    }

    private $handler;

    public function __construct(
        ESPath $localRootPath,
        ESPath $remoteRootPath = null
    )
    {
        $this->handler = ContentHandler::fold($localRootPath, $remoteRootPath);
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

        return UIKit::webView(
                $this->handler()->title(),
                UIKit::main($this->markdown(), ...$extras)
            )->meta(...$this->meta());
    }

// - Extra UI
    public function markdown()
    {
        return UIKit::markdown(
            $this->handler()->contentStore()->markdown()->content()->unfold(),
            static::markdownConfig()
        )->prepend(
            "# ". $this->handler()->title(ContentHandler::HEADING) ."\n\n". $this->detailsView() ."\n\n"
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

    public function meta(): ESArray
    {
        return Shoop::array([
            UIKit::meta()->attr(
                "name viewport",
                "content width=device-width,
                initial-scale=1"
            )
        ])->plus(...static::faviconsMeta())
        ->plus(static::socialMeta())
        ->plus(...static::stylesMeta())
        ->plus(...static::scriptsMeta());
    }

    public function socialMeta()
    {
        // https://developers.facebook.com/tools/debug/?q=https%3A%2F%2Fliberatedelephant.com%2F
        // https://cards-dev.twitter.com/validator
        return UIKit::socialMeta(
            static::socialType(),
            $this->handler()->title(ContentHandler::BOOKEND),
            url()->current(),
            $this->handler()->description(),
            $this->socialImage()
        )->twitter(...static::socialTwitter());
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
                // die(var_dump($anchors));
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
