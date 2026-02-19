# MissingPureAnnotation

Emitted when a potentially pure function or method does not have a `@psalm-pure` declaration.  

To automatically add pure annotations where needed, run Psalm with `--alter --issues=MissingPureAnnotation`.  

This issue is emitted to aid [security analysis](https://psalm.dev/docs/security_analysis/), which works best when all explicitly pure functions and methods are marked as pure.  

```php
<?php

function couldBePure(int $a): int {
    return $a+1;
}
```
