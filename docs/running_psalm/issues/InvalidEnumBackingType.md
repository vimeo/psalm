# InvalidEnumBackingType

Enums can only be backed by `int` or `string`. Emitted when an enum is backed
by something else.

```php
<?php

enum Status: array 
{
   case None = [];
}
```
