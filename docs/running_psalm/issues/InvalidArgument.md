# InvalidArgument

Emitted when a supplied function/method argument is incompatible with the method signature or docblock one.

```php
<?php

class A {}
function foo(A $a) : void {}
foo("hello");
```
