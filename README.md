<h1><img src="PsalmLogo.png" height="64" alt="logo" /></h1>

Psalm is a static analysis tool for finding errors in PHP applications, and runs in PHP 5.4+ and PHP 7.0.

Check out the [wiki](https://github.com/vimeo/psalm/wiki) or [try a live demo](http://getpsalm.org/)!

## Quickstart Guide

Install via [Composer](https://getcomposer.org/):

```bash
composer require --dev "vimeo/psalm:dev-master"
composer install
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
</psalm>
EOF
```

Then run Psalm with:

```bash
./vendor/bin/psalm
```

The above config is spartan, and will show you *all* possible errors, including many that are likely irrelevant to you. A more lenient config is provided [here](examples/psalm.default.xml).
