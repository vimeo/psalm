<img src="https://travis-ci.org/vimeo/psalm.svg?branch=master" />

<h1><a href="https://getpsalm.org"><img src="PsalmLogo.png" height="64" alt="logo" /></a></h1>

Psalm is a static analysis tool for finding errors in PHP applications.

 - **v0.3.x** supports checking PHP 5.4 - 7.1 code, and requires **PHP 5.5+** to run. 
 - **v0.2.x** supports checking PHP 5.4 - 7.0 code and requires **PHP 5.4+** to run.

Check out the [wiki](https://github.com/vimeo/psalm/wiki) or [try a live demo](https://getpsalm.org/)!

## Quickstart Guide

Install via [Composer](https://getcomposer.org/):

```bash
composer require --dev vimeo/psalm
```

Add a `psalm.xml` config:

```bash
cat > psalm.xml << EOF
<?xml version="1.0"?>
<psalm
  stopOnFirstError="false"
  useDocblockTypes="true"
>
    <projectFiles>
        <directory name="src" />
    </projectFiles>
    <issueHandlers>
        <MissingReturnType errorLevel="info" />
        <MissingClosureReturnType errorLevel="info" />
        <MissingPropertyType errorLevel="info" />
    </issueHandlers>
</psalm>
EOF
```

Then run Psalm with:

```bash
./vendor/bin/psalm
```

The above config is fairly spartan, and will show you *all* possible errors, including many that might be irrelevant to you. A more lenient config is provided [here](examples/psalm.default.xml), and you can learn about how to suppress certain issues [here](https://github.com/vimeo/psalm/wiki/Dealing-with-code-issues).

## Acknowledgements

The engineering team [@vimeo](https://github.com/vimeo) for encouragement and patience, especially [@nbeliard](https://github.com/nbeliard), [@erunion](https://github.com/erunion) and [@nickyr](https://github.com/nickyr).

Thanks also to [@nikic](https://github.com/nikic) for creating the excellent [php-parser](https://github.com/nikic/php-parser), on top of which Psalm is built.
