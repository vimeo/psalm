# InvalidEnumCaseValue

Emitted when case value is invalid (see below).

## Case with a value on a pure enum

```php
<?php

enum Status 
{
    case Open = "open";
}
```

### How to fix

Either remove the value or alter the enum to be backed.

```php
<?php

enum Status: string 
{
    case Open = "open";
}
```

## Case without a value on a backed enum

```php
<?php

enum Status: string 
{
    case Open;    
}
```

### How to fix

Either alter the enum to be pure, or add a value.

```php
<?php

enum Status 
{
    case Open;
}
```

## Case type mismatch

Case type should match the backing type of the enum.

```php
<?php

enum Status: string
{
    case Open = 1;
}
```

### How to fix

Change the types so that they match

```php
<?php

enum Status: string 
{
    case Open = "open";
}
```
