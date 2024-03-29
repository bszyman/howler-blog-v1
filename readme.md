#  Howler Microblog

> Your very own microblog.

## Important Update!

I have rebuilt Howler on top of Laravel, just for better robustness,
to not look like a scrub on the platform of the world wide internet,
and because it was a good excuse to learn Larave.

Newer version: https://github.com/bszyman/howler-blog

## About This Project

[HowlerBlog](https://howlerblow.com) is a free, open-source 
microblogging web application, designed to run on any standard 
~LAMP server.

It is built using very few dependencies, and is intentionally
framework and JavaScript-free, making this application highly 
portable and very easy to deploy.

## Features

### Microblogging

Run your own Twitter-like microblog!

(Support for image media and quoting forthcoming.)

### Bookmarking

Share a collection of cool/interesting bookmarks to
your friends. Or, keep them to yourself using the
privacy setting - your choice!

### Following

Display a list of all of your new Howler friends, or not!

### Support for Feeds - RSS, Atom and JSON

Built-in support for generating an RSS or ATOM feed of your
microblog posts. Also included is a JSON endpoint, helpful
for doing integrations into your existing web properties.

## Deploying

Similar to classic PHP apps like PHPMyAdmin, Piwigo or WordPress,
you simply need to upload the files, and set a few settings in
a configuration file.

So, steps to deploy...

1) Upload files to your webhost using SFTP or WebDav.
2) Configure the app_settings.ini file found in the /common directory.
3) Upload the blank/empty starter database (howler.sql) into your database server.

## Requirements

* Windows/MacOS/FreeBSD/Linux
* Apache
* PHP
* MySQL or MariaDB

Note: This will probably work on earlier versions of PHP,
but was built against PHP 8.1.

## Pre-Made Packages

Not a developer and just want to get this up and running?

Download one of our pre-made zip files made available in the 
[Releases](https://github.com/bszyman/howler-blog/releases) section, 
unarchive, upload and then configure.

## Building

If you plan on extending or improving Howler Blog.

* [Twig](https://twig.symfony.com/) - Server-side templating engine.
* [Psalm](https://psalm.dev/) - Static analysis tool from Vimeo.
* [OpenGraph](https://github.com/scottmac/opengraph) - Helper class for scraping OG info.

To restore dependencies:

```bash 
% cd /path/to/howler_directory
% composer install
```

### Guidance

There's no framework underpinning this application. Simply
follow a few conventions that already exist in this code, and
you should be able to build and extend this application in almost
any way, without having to be an expert in Laravel or similar.

## License

This project is licensed under MIT.

## Maintainers

* [Ben Szymanski](https://bszyman.com)
