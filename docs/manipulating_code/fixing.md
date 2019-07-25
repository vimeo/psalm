# Fixing Code

Psalm is good at finding potential issues in large codebases, but once found, it can be something of a gargantuan task to fix all the issues.

It comes with a tool, called Psalter, that helps you fix code.

You can either run it via its binary

```
vendor/bin/psalter [args]
```

or via Psalm's binary:

```
vendor/bin/psalm --alter [args]
```

## Safety features

Updating code is inherently risky, doing so automatically is even more so. I've added a few features to make it a little more reassuring:

- To see what changes Psalter will make ahead of time, you can run it with `--dry-run`.
- You can target particular versions of PHP via `--php-version`, so that (for example) you don't add nullable typehints to PHP 7.0 code, or any typehints at all to PHP 5.6 code. `--php-version` defaults to your current version.
- it has a `--safe-types` mode that will only update PHP 7 return typehints with information Psalm has gathered from non-docblock sources of type information (e.g. typehinted params, `instanceof` checks, other return typehints etc.)


## Plugins

You can pass in your own manipulation plugins e.g.
```bash
vendor/bin/psalter --plugin=vendor/vimeo/psalm/examples/plugins/ClassUnqualifier.php --dry-run
```

The above example plugin converts all unnecessarily qualified classnames in your code to shorter aliased versions.

## Supported fixes

This initial release provides support for the following alterations, corresponding to the names of issues Psalm finds.
To fix all of these at once, run `vendor/bin/psalter --issues=all`

### MissingReturnType

Running `vendor/bin/psalter --issues=MissingReturnType --php-version=7.0` on

```php
function foo() {
  return "hello";
}
```

gives

```php
function foo() : string {
  return "hello";
}
```

and running `vendor/bin/psalter --issues=MissingReturnType --php-version=5.6` on

```php
function foo() {
  return "hello";
}
```

gives

```php
/**
 * @return string
 */
function foo() {
  return "hello";
}
```

### MissingClosureReturnType

As above, except for closures

### InvalidReturnType

Running `vendor/bin/psalter --issues=InvalidReturnType` on

```php
/**
 * @return int
 */
function foo() {
  return "hello";
}
```

gives

```php
/**
 * @return string
 */
function foo() {
  return "hello";
}
```

There's also support for return typehints, so running `vendor/bin/psalter --issues=InvalidReturnType` on

```php
function foo() : int {
  return "hello";
}
```

gives

```php
function foo() : string {
  return "hello";
}
```

### InvalidNullableReturnType

Running `vendor/bin/psalter --issues=InvalidNullableReturnType  --php-version=7.1` on

```php
function foo() : string {
  return rand(0, 1) ? "hello" : null;
}
```

gives

```php
function foo() : ?string {
  return rand(0, 1) ? "hello" : null;
}
```

and running `vendor/bin/psalter --issues=InvalidNullableReturnType  --php-version=7.0` on

```php
function foo() : string {
  return rand(0, 1) ? "hello" : null;
}
```

gives

```php
/**
 * @return string|null
 */
function foo() {
  return rand(0, 1) ? "hello" : null;
}
```

### InvalidFalsableReturnType

Running `vendor/bin/psalter --issues=InvalidFalsableReturnType` on

```php
function foo() : string {
  return rand(0, 1) ? "hello" : false;
}
```

gives

```php
/**
 * @return string|false
 */
function foo() {
  return rand(0, 1) ? "hello" : false;
}
```

### MissingParamType

Running `vendor/bin/psalter --issues=MissingParamType` on

```php
class C {
  public static function foo($s) : void {
    echo $s;
  }
}
C::foo("hello");
```

gives

```php
class C {
  /**
   * @param string $s
   */
  public static function foo($s) : void {
    echo $s;
  }
}
C::foo("hello");
```

### MismatchingDocblockParamType

Given

```php
class A {}
class B extends A {}
class C extends A {}
class D {}
```

running `vendor/bin/psalter --issues=MismatchingDocblockParamType` on
```php
/**
 * @param B|C $first
 * @param D $second
 */
function foo(A $first, A $second) : void {}
```

gives

```php
/**
 * @param B|C $first
 * @param A $second
 */
function foo(A $first, A $second) : void {}
```

### MismatchingDocblockReturnType

Running `vendor/bin/psalter --issues=MismatchingDocblockReturnType` on
```php
/**
 * @return int
 */
function foo() : string {
  return "hello";
}
```

gives

```php
/**
 * @return string
 */
function foo() : string {
  return "hello";
}
```

### LessSpecificReturnType

Running `vendor/bin/psalter --issues=LessSpecificReturnType` on

```php
function foo() : ?string {
  return "hello";
}
```

gives

```php
function foo() : string {
  return "hello";
}
```

### PossiblyUndefinedVariable

Running `vendor/bin/psalter --issues=PossiblyUndefinedVariable` on

```php
function foo()
{
    if (rand(0, 1)) {
      $a = 5;
    }
    echo $a;
}
```

gives

```php
function foo()
{
    $a = null;
    if (rand(0, 1)) {
      $a = 5;
    }
    echo $a;
}
```


### PossiblyUndefinedGlobalVariable

Running `vendor/bin/psalter --issues=PossiblyUndefinedGlobalVariable` on

```php
if (rand(0, 1)) {
  $a = 5;
}
echo $a;
```

gives

```php
$a = null;
if (rand(0, 1)) {
  $a = 5;
}
echo $a;
```

### UnusedMethod

This removes private unused methods.

Running `vendor/bin/psalter --issues=UnusedMethod` on

```php
class A {
    private function foo() : void {}
}

new A();
```

gives

```php
class A {
    
}

new A();
```

### PossiblyUnusedMethod

This removes protected/public unused methods.

Running `vendor/bin/psalter --issues=PossiblyUnusedMethod` on

```php
class A {
    protected function foo() : void {}
    public function bar() : void {}
}

new A();
```

gives

```php
class A {
    
}

new A();
```

### UnusedProperty

This removes private unused properties.

Running `vendor/bin/psalter --issues=UnusedProperty` on

```php
class A {
    /** @var string */
    private $foo;
}

new A();
```

gives

```php
class A {
    
}

new A();
```

### PossiblyUnusedProperty

This removes protected/public unused properties.

Running `vendor/bin/psalter --issues=PossiblyUnusedProperty` on

```php
class A {
    /** @var string */
    public $foo;

    /** @var string */
    protected $bar;
}

new A();
```

gives

```php
class A {
    
}

new A();
```
