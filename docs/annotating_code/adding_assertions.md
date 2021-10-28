# Adding assertions

Psalm has five docblock annotations that allow you to specify that a function verifies facts about variables and properties:

- `@psalm-assert` (used when throwing an exception)
- `@psalm-assert-if-true`/`@psalm-assert-if-false` (used when returning a `bool`)
- `@psalm-if-this-is`/`@psalm-this-out` (used when calling a method)

A list of acceptable assertions [can be found here](assertion_syntax.md).

## Examples

If you have a class that verified its input is an array of strings, you can make that clear to Psalm:

```php
<?php
/** @psalm-assert string[] $arr */
function validateStringArray(array $arr) : void {
    foreach ($arr as $s) {
        if (!is_string($s)) {
          throw new UnexpectedValueException('Invalid value ' . gettype($s));
        }
    }
}
```

This enables you to call the `validateStringArray` function on some data and have Psalm understand that the given data *must* be an array of strings:

```php
<?php
function takesString(string $s) : void {}
function takesInt(int $s) : void {}

function takesArray(array $arr) : void {
    takesInt($arr[0]); // this is fine

    validateStringArray($arr);

    takesInt($arr[0]); // this is an error

    foreach ($arr as $a) {
        takesString($a); // this is fine
    }
}
```

Similarly, `@psalm-assert-if-true` and `@psalm-assert-if-false` will filter input if the function/method returns `true` and `false` respectively:

```php
<?php
class A {
    public function isValid() : bool {
        return (bool) rand(0, 1);
    }
}
class B extends A {
    public function bar() : void {}
}

/**
 * @psalm-assert-if-true B $a
 */
function isValidB(A $a) : bool {
    return $a instanceof B && $a->isValid();
}

/**
 * @psalm-assert-if-false B $a
 */
function isInvalidB(A $a) : bool {
    return !$a instanceof B || !$a->isValid();
}

function takesA(A $a) : void {
    if (isValidB($a)) {
        $a->bar();
    }

    if (isInvalidB($a)) {
        // do something
    } else {
        $a->bar();
    }

    $a->bar(); //error
}
```

As well as getting Psalm to understand that the given data must be a certain type, you can also show that a variable must be not null:

```php
<?php
/**
 * @psalm-assert !null $value
 */
function assertNotNull($value): void {
  // Some check that will mean the method will only complete if $value is not null.
}
```

And you can check on null values:

```php
<?php
/**
 * @psalm-assert-if-true null $value
 */
function isNull($value): bool {
  return ($value === null);
}
```

### Asserting return values of methods

You can also use the `@psalm-assert-if-true` and `@psalm-assert-if-false` annotations to assert return values of
methods inside classes. As you can see, Psalm even allows you to specify multiple annotations in the same DocBlock:

```php
<?php
class Result {
    /**
     * @var ?Exception
     */
    private $exception;

    /**
     * @psalm-assert-if-true Exception $this->exception
     * @psalm-assert-if-true Exception $this->getException()
     */
    public function hasException(): bool {
        return $this->exception !== null;
    }

    public function getException(): ?Exception {
        return $this->exception;
    }

    public function foo(): void {
        if( $this->hasException() ) {
            // Psalm now knows that $this->exception is an instance of Exception
            echo $this->exception->getMessage();
        }
    }
}

$result = new Result;

if( $result->hasException() ) {
    // Psalm now knows that $result->getException() will return an instance of Exception
    echo $result->getException()->getMessage();
}
```

Please note that the example above only works if you enable [method call memoization](https://psalm.dev/docs/running_psalm/configuration/#memoizemethodcallresults)
in the config file or annotate the class as [immutable](https://psalm.dev/docs/annotating_code/supported_annotations/#psalm-immutable).


You can use `@psalm-this-out` to change the template arguments of a method after a method call, to reflect changes to the object's internal state.  
You can also make assertions on the object's template arguments using `@psalm-if-this-is`.  


```php
<?php

/**
 * @template T
 */
class a {
    /**
     * @var list<T>
     */
    private array $data;
    /**
     * @param T $data
     */
    public function __construct($data) {
        $this->data = [$data];
    }
    /**
     * @template NewT
     * 
     * @param NewT $data
     * 
     * @psalm-this-out self<T|NewT>
     * 
     * @return void
     */
    public function addData($data) {
        /** @var self<T|NewT> $this */
        $this->data []= $data;
    }
    /**
     * @template NewT
     * 
     * @param NewT $data
     * 
     * @psalm-this-out self<NewT>
     * 
     * @return void
     */
    public function setData($data) {
        /** @var self<NewT> $this */
        $this->data = [$data];
    }
    /**
     * @psalm-if-this-is a<int>
     */
    public function test(): void {
    }
}

$i = new a(123);
// OK - $i is a<123>
$i->test();

$i->addData(321);
// OK - $i is a<123|321>
$i->test();

$i->setData("test");
// IfThisIsMismatch - Class is not a<int> as required by psalm-if-this-is
$i->test();
```
