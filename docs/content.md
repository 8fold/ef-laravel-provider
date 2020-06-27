# Content folder structure

8fold Laravel Provider uses a one-to-one relationship between the URL less the domain (path).

So, this path: `/path/to/content`

Becomes this folder structure:

- /root
    - /path
        - /to
            - /content

Or the file path: `/root/path/to/user/root/path/to/content`

The `root` folder gives the provider a starting point.

For paths you want to return valid should contain a `content.md` file.

The `content.md` file contain Markdown and can be separated into the content and the metadata.

Content is traditional Markdown and will be parsed as such using [8fold Shoop Extras](https://github.com/8fold/php-shoop-extras), which uses a Markdown parser we appreciate.

Metadata uses [.YAML](Yet Another Markup Language) and will be an `ESObject` when you interact with it.

## Metadata

Metadata is delineated from the content by YAML surrounded by three hyphens before and after.

```
---
{YAML}
---
```

8fold Laravel Provider tries to automate a fair amount of building out pages. Therefore, the following members are used for the noted purposes.

|YAML member    |Notes                                                                                                 |
|:--------------|:-----------------------------------------------------------------------------------------------------|
|title          |(required) Used to generate titles. Optionally used to generate the main heading of the page.         |
|heading        |Allows you to have different content for the page title and main heading of the page.                 |
|created        |A date (no time) formatted as a four digit year, two digit month, and two digit day representing the day the content was created. ex. 2020101 is Jan 1st 2020. |
|moved          |A date (no time) formatted as a four digit year, two digit month, and two digit day representing the day the content was moved from an originating platform. ex. 20200201 is Feb 1st 2020. |
|original       |A Markdown link to the original post on the original platform or the name of the originating platform.|
|posterAlt      |`alt` text for a poster image.                                                                        |
|description    |Used primarily for [.RSS](Real Simple Syndication) and sharing metadata. When not present, a 50 word description will be generated from the content. |
|format         |An [Open Graph type](https://ogp.me/#types) representing the content. (This will also be used for generating sharing metadata.) |
|toc            |A list of paths representing content for a table of contents.                                         |
|rssTitle       |A title to be used for the RSS feed itself.                                                           |
|rssDescription |A description to be used for the RSS feed itself.                                                     |
|rssLink        |The domain or link to the website of the channel for the RSS feed, not the feed itself.               |

You can add other tags to the metadata area (and we may as well). In other words, these tags are just those recognized by the content builder.

## Reserved names

- **content.md:** This file is used for automating content generation. The reserve name scope is limited to the content folder and subfolders.
- **poster.jpg:** This file is used for automating social sharing capabilities. The reserve name scope is limited to the `.media` folder.
