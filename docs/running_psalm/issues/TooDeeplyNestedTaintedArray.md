# TooDeeplyNestedTaintedArray

Emitted by [security analysis](https://psalm.dev/docs/security_analysis/) when tainted data nested more than 4 levels deep in arrays is passed to a function or method whose taints are specialized to each call (pure functions and methods, including unannotated ones Psalm infers to be pure, or ones with `@psalm-taint-specialize`).

Security analysis only tells such calls apart by the 4 outermost levels of nesting of their arguments, so it may miss taint flowing through the deeper levels. Reduce the nesting to fix taint analysis.

```php
<?php

/**
 * @psalm-pure
 * @param array{a: array{b: array{c: array{d: array{e: string}}}}} $a
 */
function get5(array $a): string {
    return $a["a"]["b"]["c"]["d"]["e"];
}

$e = $_GET["e"];

if (is_string($e)) {
    echo get5(["a" => ["b" => ["c" => ["d" => ["e" => $e]]]]]);
}
```
