# RedundantFlag

Emitted when a flag is redundant. e.g. FILTER_NULL_ON_FAILURE won't do anything when the default option is specified

```php
<?php
$x = filter_input(INPUT_GET, 'hello', FILTER_VALIDATE_DOMAIN, array('options' => array('default' => 'world.com'), 'flags' => FILTER_NULL_ON_FAILURE));
```
