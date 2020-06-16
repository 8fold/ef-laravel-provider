<?php

namespace Eightfold\Site;

use Carbon\Carbon;

use Spatie\YamlFrontMatter\YamlFrontMatter;

use Eightfold\Shoop\{
    Shoop,
    ESArray,
    ESString
};

use Eightfold\Markup\UIKit;
use Eightfold\Markup\Element;

use Eightfold\Site\Shoop\ESMarkdown;

abstract class ContentBuilder
{
    abstract static public function view(...$content);

    abstract static public function domain(): ESString;

    abstract static public function copyrightContent(): ESString;

    static public function faviconPack()
    {
        return Shoop::array([
            UIKit::link()->attr("type image/x-icon", "rel icon", "href /assets/favicons/favicon.ico"),
            UIKit::link()->attr("rel apple-touch-icon", "href /assets/favicons/apple-touch-icon.png", "sizes 180x180"),
            UIKit::link()->attr("rel image/png", "href /assets/favicons/favicon-32x32.png", "sizes 32x32"),
            UIKit::link()->attr("rel image/png", "href /assets/favicons/favicon-16x16.png", "sizes 16x16")
        ]);
    }

    static public function stylesheets()
    {
        return Shoop::array([])
            ->plus(
                UIKit::link()->attr("rel stylesheet", "href ". self::assetPath("css"))
            );
    }

    static public function javascripts()
    {
        return Shoop::array([])
            ->plus(
                UIKit::script()->attr("src ". self::assetPath("js"))
            );
    }

    static public function assetPath(string $extension): ESString
    {
        $folder = $extension;
        $shortName = static::shortName();
        $fileName = Shoop::array(["main", $extension])->join(".");
        $uri = Shoop::array([])->plus($folder, $shortName, $fileName)
            ->noEmpties()->join("/")->start("/");
        return $uri;
    }

    static public function pageTitle()
    {
        $uriRoot = static::uriRoot();

        $contentPathRoot = static::contentPathParts()->last;
        $titleContentPathParts = static::uriContentPathParts();

        $break = false;
        return $titleContentPathParts->toggle()
            ->each(function($part, $index) use ($uriRoot, $contentPathRoot, &$titleContentPathParts, &$break) {
                $return = "";
                if ($break) {
                    $return = "";

                } elseif ($part === $contentPathRoot) {
                    $break = true;
                    $path = $titleContentPathParts;
                    $return = static::titleForPathParts($path);

                } else {
                    $path = $titleContentPathParts;
                    $return = static::titleForPathParts($path);
                }
                $titleContentPathParts = $titleContentPathParts->dropLast();
                return $return;
            })->noEmpties()->join(" | ");
    }

    static public function titleForPathParts(ESArray $pathParts): string
    {
        return static::markdownForPathParts($pathParts)->isEmpty(function($result, $value) {
            if ($result) {
                return Shoop::string("");
            }
            $title = ESMarkdown::fold($value)->meta()->title;
            return ($title === null) ? "" : $title;
        });
    }

    static public function descriptionForPathParts(ESArray $pathParts): string
    {
        return static::markdownForPathParts($pathParts)->isEmpty(function($result, $value) {
            if ($result) {
                return Shoop::string("");
            }

            $markdown = ESMarkdown::fold($value);
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

// -> Markdown
    static public function markdown(string $path = "")
    {
        return Shoop::string($path)->isEmpty(function($result, $value) use ($path) {
            if ($result) {
                return ESMarkdown::fold(static::contentForUri());
            }
            return static::contentPath($path)->plus("/content.md")->pathContent()
                ->isEmpty(function($result, $value) use ($path) {
                    if ($result) {
                        return ESMarkdown::fold("");
                    }
                    return ESMarkdown::fold($value);
                });
        });
    }

    static public function markdownForPathParts(ESArray $pathParts)
    {
        $content = $pathParts->plus("content.md")->noEmpties()
            ->join("/")->start("/")->pathContent();
        return ESMarkdown::fold($content);
    }

    static public function contentForUri(string $fileName = "content.md")
    {
        if (! file_exists(static::uriContentPath($fileName))) {
            return "";
        }
        return static::uriContentPath($fileName)->pathContent();
    }

// -> Helpers
    static public function uriPathParts(): ESArray
    {
        $parts = Shoop::string(url()->current())->divide("/")->dropFirst(3)->noEmpties();
        return $parts;
    }

    static public function uriRoot(): string
    {
        $uriParts = static::uriPathParts();
        $return = ($uriParts->isEmpty) ? "" : $uriParts->first;
        return $return;
    }

    static public function uriContentPathParts()
    {
        $uriParts = static::uriPathParts();
        return static::contentPathParts()->plus(...$uriParts);
    }

    static public function uriContentPath(string $fileName = "content.md")
    {
        return static::uriContentPathParts()
            ->plus($fileName)->join("/")->start("/");
    }

    static public function contentPath(string $path = "")
    {
        return Shoop::string($path)->isEmpty(function($result, $value) {
            if ($result) {
                return static::contentPathParts()->join("/");
            }
            $value = Shoop::string($value)->divide("/");
            return static::contentPathParts()
                ->plus(...$value)->noEmpties()->join("/")->start("/");
        });
    }

    abstract static public function contentPathParts(): ESArray;

    abstract static public function assetsPathParts(): ESArray;

    static public function shortName(): ESString
    {
        return Shoop::string("");
    }
}
