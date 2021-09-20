# DuplicateEnumCaseValue

Emitted when a backed enum has duplicate case values.

```php
<?php

enum Status: string 
{
    case Open = "open";
    case Closed = "open";
}
```

## How to fix

Change case values so that there are no duplicates.

```php
<?php

enum Status: string 
{
    case Open = "open";
    case Closed = "closed";
}
```
