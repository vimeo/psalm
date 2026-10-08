# ImpureStaticProperty

Emitted when attempting to use a static property from a function or method marked as pure

```php
<?php

class ValueHolder {
    public static ?string $value = null;

    /**
     * @psalm-pure
     */
    public static function get(): ?string {
        return self::$value;
    }
}
```

See [capabilities](../../annotating_code/purity_model.md#capabilities) in the purity model for what each capability allows.
