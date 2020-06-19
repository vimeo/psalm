# Custom Taint Sinks

You can define your own taint sinks two ways

## Using a docblock annotation

`@psalm-taint-sink`

```php
<?php

class PDOWrapper {
    /**
     * @psalm-taint-sink sql $sql
     */
    public function exec(string $sql) : void {}
}
```

## Using a Psalm plugin

or with a plugin

