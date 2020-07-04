<?php

namespace Eightfold\Site\Helpers;

use Eightfold\Site\Helpers\ContentStore;

use Eightfold\ShoopExtras\{
    Shoop,
    ESUri,
    ESStore
};

use Eightfold\Shoop\Helpers\Type;

class Uri
{
    private $uri;
    private $contentStore;

    /**
     * Title member from YAML front matter.
     */
    public const TITLE = "title";

    /**
     * Heading member from YAML front matter, falls back to title.
     */
    public const HEADING = "heading";

    /**
     * Recursively uses title member from YAML front matter to build a fully-
     * qualified title string with separator. ex. Leaf | Branch | Trunk | Root
     */
    public const PAGE = "page";

    /**
     * Uses the title member from YAML front matter to build a two-part title,
     * which includes the title for the current URL plus the title of the root
     * page with a separater. ex. Leaf | Root
     */
    public const SHARE = "share";

    /** @deprecated */
    // private $dir;

    /** @deprecated */
    // private $contentFolderName;

    static public function fold(
        $uri = "",
        $contentStorePath = __DIR__
    )
    {
        return new static($uri, $contentStorePath);
    }

    public function __construct($uri = "", $contentStorePath = "")
    {
        $this->uri = Type::sanitizeType($uri, ESString::class);
        $this->contentStorePath = Type::sanitizeType($contentStorePath, ESString::class);
    }

    public function unfold()
    {
        return $this->uri()->unfold();
    }

    public function uri()
    {
        return $this->uri;
    }















    public function root()
    {
        return static::parts($this->uri())->isEmpty(function($result, $array) {
            return ($result) ? Shoop::string("/") : $array->first()->start("/");
        });
    }

    public function parts()
    {
        return $this->uri()->isEmpty(function($result, $uri) {
                return ($result)
                    ? Shoop::string(request()->path())->start("/")->divide("/", false)
                    : $this->uri()->divide("/");
            })->noEmpties()->reindex();
    }

    public function markdown($fileName = "content.md")
    {
        return $this->content("", $fileName)->isFile(
            function($result, $store) {
                return ($result)
                    ? $store->markdown()->content()
                    : Shoop::string("");
            });
    }

    public function meta($fileName = "content.md")
    {
        return $this->content("", $fileName)->isFile(
            function($result, $store) {
                return ($result)
                    ? $store->markdown()->meta()
                    : Shoop::object(new \stdClass());
            });
    }

    /**
     * Use for share, heading, title, page
     */
    public function title()
    {
        $anchors = Shoop::array([])->noEmpties()->reindex();
        $toc->each(function($uri) use (&$anchors) {
            $title = static::uriContentMarkdownMetaForMember("title", $uri);
            if ($title === null) {
                $anchors = $anchors->plus("");
                return;
            }
            $href = $uri;
            $anchors = $anchors->plus(
                UIKit::anchor($title, $href)
            );
        });
    }
// -> Stores/Folders
    private function contentStore(): ESStore
    {
        return $this->contentStore;
    }

    public function store(): ESStore
    {
        return $this->contentStore()->store();
    }

    public function content($fileName = "content.md")
    {
        return $this->contentStore()->uri($this->uri())->content($fileName);
    }

    public function assets($folderName = ".assets")
    {
        return $this->store()->plus(".assets");
    }

    public function media($uri = "")
    {
        $uri = Type::sanitizeType($uri, ESString::class)->divide("/", false);
        return $this->store()->plus(".media")->plus(...$uri);
    }
}
