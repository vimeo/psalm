# UnusedMethodCall

Emitted when `--find-dead-code` is turned on and Psalm finds a method call whose return value is not used anywhere

```php
<?php

final class A {
    private string $foo;

    public function __construct(string $foo) {
        $this->foo = $foo;
    }

    public function getFoo() : string {
        return $this->foo;
    }
}

$a = new A("hello");
$a->getFoo();
```

With `--find-dead-code` it is also emitted for an exception (any `Throwable`) that is created but never thrown, returned or otherwise used, whatever its constructor does:

```php
<?php

function check(int $i): void {
    if ($i < 0) {
        new InvalidArgumentException("negative"); // missing throw
    }
}
```

It is also emitted, regardless of `--find-dead-code`, when the return value of a method annotated with PHP 8.5's `#[\NoDiscard]` attribute is discarded. Casting the call to `(void)` intentionally discards the value without triggering the issue.
