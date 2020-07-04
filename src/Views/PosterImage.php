<?php

namespace Eightfold\Site\Views;

use Eightfold\Site\ContentBuilder;

use Eightfold\Markup\UIKit;

use Eightfold\ShoopExtras\Shoop;

use Eightfold\Shoop\Helpers\Type;
use Eightfold\Shoop\ESString;

class PosterImage
{
    static public function view($contentBuilderClass, $uri, $search = false, $ext = "jpg")
    {
        $contentBuilder = Type::sanitizeType($contentBuilderClass, ESString::class)->unfold();
        $uri            = Type::sanitizeType($uri, ESString::class);

        // Don't use parent - posterAlt: /
        return UIKit::image(
            "The eightfold logo.",
            "http://localhost/poster.jpg"
        );
        // Don't use parent - no posterAlt: /somewhere/else
        // Use parent - posterAlt: /somewhere/else (becomes /)
        //
        //
        //
        //
        //
        //
        //
        //
        //
        //
        //
        //
        //
        //
        //
//         $contentStore = $contentBuilder::uriContentStore($uri)->parent();
//         $s = $uri->divide("/")->each(
//             function($part, $member, &$break) use ($contentBuilder, &$contentStore) {
//                 return $contentStore->plus("content.md")->isFile(function($result, $s) use (&$contentStore, &$break) {
//                     $altText = $s->markdown()->meta()->posterAlt;
//                     $hasAlt  = $altText !== null;
//                     if ($result and $hasAlt) {
//                         $break = true;
//                         return $s;

//                     }
//                     $contentStore = $contentStore->parent();
//                     return Shoop::string("");
//                 });
//             })->noEmpties()->first();

//         if ($s->isEmpty) {
//             return Shoop::string("");
//         }

//         return $contentBuilder::contentStore()->string()->replace([$contentStore->value() => ""])
//             ->isEmpty(function($result, $string) use ($ext, $contentBuilder, $contentStore, $s) {
//                 if (! $result) {
//                     die("do something with a uri");
//                 }

//                 $imageParts = Shoop::array(["poster.{$ext}"]);
//                 if (Shoop::string($string)->count()->isNotUnfolded(0)) {
//                     $imageParts = $imageParts->start($string);
//                 }

//                 $imageStore = $contentBuilder::mediaStore()->plus(...$imageParts)
//                     ->isFile(function($result) use ($ext, $contentBuilder, $contentStore, $s) {
//                         if (! $result) {
//                             return Shoop::string("");
//                         }
//                         die(var_dump($s));
//                     });
//                 // if ($result) {
//                 //             return $contentBuilder::mediaStore()
//                 //                 ->plus(...Shoop::array([
//                 //                     $string,
//                 //                     "poster.{$ext}"
//                 //                 ])->noEmpties()->isFile(function($result, $mediaStore) use ($s) {
//                 //                     $altText  = $s->markdown()->meta()->posterAlt;
//                 //                     $imageUri = Shoop::store(request()->root())
//                 //                         ->plus("media", ...$contentBuilder::uriParts())
//                 //                         ->plus("poster.jpg")
//                 //                         ->value();
//                 //                     return UIKit::image(
//                 //                         $posterAlt,
//                 //                         $imageUri
//                 //                     )->attr("class poster");
//                 //                 });
//                 //         }
//                 //         return Shoop::string("");
//                 //     });
//             });
//         die(var_dump($uriImage));


//         $uriContent = $uri->plus("/content.md");

//         $noImage   = $contentBuilder::mediaStore($uriImage)->isFile()->not;
//         $noContent = $contentBuilder::contentStore($uriContent)->isFile()->not;
//         // if there isn't a poster image for the current URI - go to parent.
//         // if there is a poster image for the URI - check if there is content
//         // if there
//         if ($noImage or $noContent) {
//             return Shoop::string("");
//         }

//         $posterAlt = $contentBuilder::contentStore($uriContent)
//             ->markdown()->meta()->posterAlt;

//         if ($posterAlt === null) {
//             return Shoop::string("");
//         }


//         $return = $uri->divide("/")->each(
//             function($part, $member, &$break) use ($contentBuilder, $uriImage, &$store) {
//                 $potential = $contentBuilder::mediaStore($uriImage);
//                 if ($potential->isFile) {
//                     $break = true;
//                     return Shoop::store(request()->root())
//                         ->plus("media", ...$contentBuilder::uriParts())
//                         ->plus("poster.jpg");
//                 }
//                 $store = $store->parent();
//                 return "";
//         })->noEmpties()->isEmpty(function($result, $paths) {
//             return ($result) ? "" : $paths->first();

//         })->isEmpty(function($result, $imageUri) use ($posterAlt) {
//             return ($result)
//                 ? ""
//                 : UIKit::image(
//                         $posterAlt,
//                         $imageUri
//                     )->attr("class poster");
//         });
// die(var_dump($return->unfold()));
//         return UIKit::image($posterAlt);
// die($posterAlt);
//         return $contentBuilder::mediaStore($uri)->isFile(
//             function($result, $mediaStore) use ($uri, $contentBuilder) {
//                 if (!$result) {
//                     return Shoop::string("");
//                 }
//                 return $contentBuilder::contentStore($uri)->isFile(
//                     function($result, $contentStore) use ($mediaStore) {
//                         if (!$result) {
//                             return Shoop::string("");
//                         }
//                         return Shoop::store(request()->root())
//                             ->plus("media", ...$contentBuilder::uriParts())
//                             ->plus("poster.jpg");
//                 die(var_dump($contentStore));
//                 });
//         });
//         return $contentBuilder::uriContentMarkdown($uri)->meta()->posterAlt()
//             ->isEmpty(function($result, $posterAlt) {
//                 return ($result) ? "" : UIKit::image(
//                         $posterAlt,
//                         static::uriPosterUrl($uri)
//                     )->attr("class poster");
//             });
    }

    static private function uriPosterUrl($uri = "")
    {
        $store = ContentBuilder::uriContentStore($uri)->parent();
        return Shoop::string(ContentBuilder::uri())->divide("/")
            ->each(function($part, $member, &$break) use (&$store) {
                $potential = ContentBuilder::mediaStore()->plus(
                    ...ContentBuilder::uriParts()->plus("poster.jpg")
                );
                if ($potential->isFile) {
                    $break = true;
                    return Shoop::store(request()->root())
                        ->plus("media", ...ContentBuilder::uriParts())
                        ->plus("poster.jpg");
                }
                $store = $store->parent();
                return "";

        })->noEmpties()->isEmpty(function($result, $contents) {
            if ($result) {
                return "";
            }
            return $contents->first;
        });
    }
}
