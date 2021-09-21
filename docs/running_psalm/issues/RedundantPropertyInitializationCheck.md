# RedundantPropertyInitializationCheck

Emitted when checking `isset()` on a non-nullable property. This issue indicate a redundant check for projects that initialize their properties in constructor.

```php
<?php
    class A {
        public string $bar;
        public function getBar() : string {
            if (isset($this->bar)) {
                return $this->bar;
            }
            return "hello";
        }
    }
```
