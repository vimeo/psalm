# UndefinedConstant

Emitted when referencing a constant that does not exist

```php
<?php

echo FOO_BAR;
```

Is also emitted when using extension constants not added to the `composer.json` requirements or to the [enableExtensions](https://psalm.dev/docs/running_psalm/configuration/#enableextensions) config key.  
A native constant newer than the analysed PHP version is also reported, unless a check guards it or the code defines it:

```php
<?php // phpVersion="8.1"

echo PHP_BUILD_DATE; // UndefinedConstant: introduced in PHP 8.5
echo PHP_VERSION_ID >= 80500 ? PHP_BUILD_DATE : ''; // no issue
if (defined('PHP_BUILD_DATE')) {
    echo PHP_BUILD_DATE; // no issue
}
```
