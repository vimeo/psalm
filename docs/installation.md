# Installation

Psalm Requires PHP >= 5.6 and [Composer](https://getcomposer.org/).

```bash
composer require --dev vimeo/psalm
```

Add a `psalm.xml` config:

```bash
./vendor/bin/psalm --init [source_directory=src] [config_level=3]
```

where `config_level` represents how strict you want Psalm to be. `1` is the strictest, `7` is the most lenient.

Then run Psalm:

```bash
./vendor/bin/psalm
```
