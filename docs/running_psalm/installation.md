# Installation

The latest version of Psalm requires PHP >= 8.2 and [Composer](https://getcomposer.org/).

```bash
composer require --dev vimeo/psalm
```

Generate a config file:

```bash
./vendor/bin/psalm --init
```

Psalm will scan your project and figure out an appropriate [error level](error_levels.md) for your codebase.

Then run Psalm:

```bash
./vendor/bin/psalm --no-cache
```

Psalm will probably find a number of issues - find out how to deal with them in [Dealing with code issues](dealing_with_code_issues.md).

## Docker image

It is recommended to run Psalm in the official docker image: it uses a custom build of PHP built from scratch, running Psalm **+30% faster** on average than normal PHP (**+50% faster** if comparing to PHP without opcache installed).  

```bash
docker run -v $PWD:/app --rm -it ghcr.io/danog/psalm:latest /composer/vendor/bin/psalm --no-cache
```

More specific tags are also available (i.e. `ghcr.io/danog/psalm:6.9.1`, as well as branch tags).   

Issues due to missing extensions can be fixed by enabling them in psalm.xml and/or requiring them in composer.json [see here &raquo;](https://psalm.dev/docs/running_psalm/configuration/#enableextensions) for more info.

Extensions not stubbed by Psalm itself (and thus not available as a psalm config option) may be stubbed using [traditional PHP stubs](https://github.com/JetBrains/phpstorm-stubs/).

## Installing plugins

While Psalm can figure out the types used by various libraries based on
their source code and docblocks, it works even better with custom-tailored types
provided by Psalm plugins.

Check out the [list of existing plugins on Packagist](https://packagist.org/?type=psalm-plugin). 
Install them with `composer require --dev <plugin/package> && vendor/bin/psalm-plugin enable <plugin/package>`

Read more about plugins in [Using Plugins chapter](plugins/using_plugins.md).

## Using the Phar

Sometimes your project can conflict with one or more of Psalm’s dependencies. In
that case you may find the Phar (a self-contained PHP executable) useful.

The Phar can be downloaded from GitHub:

```bash
wget https://github.com/vimeo/psalm/releases/latest/download/psalm.phar
chmod +x psalm.phar
./psalm.phar --version
```

Alternatively, you can use Composer to install the Phar:

```bash
composer require --dev psalm/phar
```
