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

## Case with a type that cannot back an enum

Case type should be either `int` or `string`.

```php
<?php

enum Status: int {
    case Open = [];
}
```

### How to fix

Change the case value so that it's one of the allowed types (and matches the backing type)

```php
<?php

enum Status: int
{
    case Open = 1;
}
```
