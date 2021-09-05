# DuplicateEnumCase

Emitted when enum has duplicate cases.

```php
<?php

enum Status 
{
    case Open;
    case Open;
}
```

## How to fix

Remove or rename the offending duplicates.

```php
<?php

enum Status 
{
    case Open;
    case Closed;
}
```
