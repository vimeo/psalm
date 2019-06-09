# Supported types

Psalm allows you to specify type information. This is used in a few places:

Psalm assertions:
```php
  @psalm-assert <type> $value
```

And also constraining templates: 
```php
  @template T of <type> 
```

Where `<type>` can be one of:

| Type     | Template constraint | Assertion | Notes |
|----------|---------------------|-----------|-------|
| bool  | N | Y |  |
| int   | N | Y |  |
| string   | N | Y |  |
| float   | N | Y |  |
| array   | N | Y |  |
| iterable   | Y | Y |  |
| object   | Y | Y |  |
| callable   | Y | Y |  |
| resource   | Y | Y |  |
| null   | N | Y |  |
| numeric  | Y | Y | int, float, string (as string could represent a number) |
| scalar  | Y | Y | int, float, string, bool |
| never-return  | N | Y | Means function never returns |
| true  | N | Y |  |
| false  | N | Y |  |
| empty  | N | Y |  |
| array-key  | Y | Y | int or string |
| (user class)  | Y | Y |  |





