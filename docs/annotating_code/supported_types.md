# Supported types

Psalm allows you to specify type information. This is used in a few places:

Psalm assertions:
```php
  @psalm-assert <type> $value
```

Defining the type of a template:
```php
/**
 * @template T
 */
class SomeClass { ... }   

/** @var SomeClass<type> $myClass
$myClass = new SomeClass();
```

And also constraining templates: 
```php
  @template T of <type> 
```

Where `<type>` can be one of:

| Type          | Template Type | Template constraint | Assertion | Notes |
|---------------|---------------|---------------------|-----------|-------|
| bool          | Y             | N                   | Y         |       |
| int           | Y             | N                   | Y         |       |
| string        | Y             | N                   | Y         |       |
| float         | Y             | N                   | Y         |       |
| array         | Y             | N                   | Y         |       |
| iterable      | Y             | Y                   | Y         |       |
| object        | Y             | Y                   | Y         |       |
| callable      | Y             | Y                   | Y         |       |
| resource      | Y             | Y                   | Y         |       |
| null          | N             | N                   | Y         |       |
| numeric       | Y             | Y                   | Y         | int, float, string (as string could represent a number) |
| scalar        | Y             | Y                   | Y         | int, float, string, bool |
| never-return  | N             | N                   | Y         | Means function never returns |
| true          | N             | N                   | Y         |       |
| false         | N             | N                   | Y         |       |
| empty         | N             | N                   | Y         |       |
| array-key     | Y             | Y                   | Y         | int or string |
| (user class)  | Y             | Y                   | Y         |       |





