# Installation

Psalm Requires PHP >= 5.6 and [Composer](https://getcomposer.org/).

```bash
composer require --dev vimeo/psalm
```

Add a `psalm.xml` config:

```bash
./vendor/bin/psalm --init [source_directory=src] [config_level=3]
```

Then run Psalm:

```bash
./vendor/bin/psalm
```
