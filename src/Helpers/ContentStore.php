<?php

namespace Eightfold\Site\Helpers;

use Carbon\Carbon;

use Eightfold\ShoopExtras\{
    Shoop,
    ESStore,
    ESMarkdown
};

use Eightfold\Shoop\Helpers\Type;

use Eightfold\Shoop\{
    ESArray,
    ESString,
    ESObject
};

use Eightfold\Site\Helpers\Uri;
use Eightfold\Site\Helpers\ContentStore;

class ContentStore
{
    private $uri;
    private $store;

    static public function fold($uri, $contentStorePath): ContentStore
    {
        return new static($uri, $contentStorePath);
    }

    public function __construct($uri, $contentStorePath)
    {
        $uri = Type::sanitizeType($uri, ESString::class);
        $contentStorePath = Type::sanitizeType($contentStorePath, ESString::class);

        $this->uri = Shoop::uri($uri);
        $this->store = Shoop::store($contentStorePath);
    }

    public function unfold()
    {
        return $this->path()->unfold();
    }

    public function path()
    {
        return $this->contentStorePath;
    }




















    public function uri($uri = ""): ContentStore
    {
        $this->uri = Shoop::string($uri);
        return $this;
    }

    public function markdown($fileName = "content.md"): ESMarkdown
    {
        return $this->content($fileName)->isFile(
            function($result, $store) {
                return ($result)
                    ? $store->markdown()->content()
                    : Shoop::string("");
            });
    }

// -> Meta reserved
    public function meta($fileName = "content.md"): ESObject
    {
        return $this->content($fileName)->isFile(
            function($result, $store) {
                return ($result)
                    ? $store->markdown()->meta()
                    : Shoop::object(new \stdClass());
            });
    }

    public function metaMember($member = "")
    {
        $member = Type::sanitizeType($member, ESString::class);
        if ($this->meta()->hasMember($member)->unfold()) {
            return $this->meta()->get($member);
        }
        return Shoop::string("");
    }

    public function toc($fileName = "content.md"): ESArray
    {
        $toc = $this->metaMember("toc");
        if ($toc === null) {
            return Shoop::array([]);
        }
        return $toc;
    }

    public function details(
        $modifiedLabel = "Modified on: ",
        $createdLabel  = "Created on: ",
        $movedLabel    = "Moved on: "
    )
    {
        return Shoop::array([
            $this->modified($modifiedLabel),
            $this->created($createdLabel),
            $this->moved($modifiedLabel)
        ])->noEmpties();
    }

    public function modified($label = "Modified on: ")
    {
        return $this->stringForDate("modified", $label);
    }

    public function created($label = "Created on: ")
    {
        return $this->stringForDate("created", $label);
    }

    public function moved($label = "Moved on: ")
    {
        return $this->stringForDate("moved", $label);
    }

    public function stringForDate($member, $label, $fileName = "content.md")
    {
        $meta = $this->meta($fileName);

        return ($meta->{$member} === null)
            ? Shoop::string("")
            : Shoop::string($label)->plus(
                    Carbon::createFromFormat("Ymd", $meta->{$member}, "America/Chicago")
                        ->toFormattedDateString()
                );
    }

// -> Stores/Folders
    public function store(): ESStore
    {
        return Shoop::store($this->base)->plus($this->contentFolderName);
    }

    public function content($fileName = "content.md")
    {
        return $this->store()
            ->plus(...Shoop::string($this->uri)->divide("/", false))
            ->plus($fileName);
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
