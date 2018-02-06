# Issue types

### AbstractInstantiation

Emitted when an attempt is made to instatiate an abstract class:

```php
abstract class A {}
new A();
```

### AssignmentToVoid

Emitted when assigning from a function that returns `void`:

```php
function foo() : void {}
$a = foo();
```

### CircularReference

Emitted when a class references itself as one of its parents

```php
class A extends B {}
class B extends A {}
```

### ConflictingReferenceConstraint

Emitted when a by-ref variable is set in two different branches of an if to different types.

```php
 class A {
    /** @var int */
    private $foo;

    public function __construct(int &$foo) {
        $this->foo = &$foo;
    }
}

class B {
    /** @var string */
    private $bar;

    public function __construct(string &$bar) {
        $this->bar = &$bar;
    }
}

if (rand(0, 1)) {
    $v = 5;
    $c = (new A($v)); // $v is constrained to an int
} else {
    $v = "hello";
    $c = (new B($v)); // $v is constrained to a string
}

$v = 8;
```

### ContinueOutsideLoop

Emitted when encountering a `continue` statement outside a loop context.

```php
$a = 5;
continue;
```

### DeprecatedClass

Emitted when referring to a deprecated class:

```php
/** @deprecated */
class A {}
new A();
```

### DeprecatedInterface

Emitted when referring to a deprecated interface

```php
/** @deprecated */
interface I {}

class A implements I {}
```

### DeprecatedMethod

Emitted when calling a deprecated method on a given class:

```php
class A {
    /** @deprecated */
    public function foo() : void {}
}
(new A())->foo();
```

### DeprecatedProperty

Emitted when getting/setting a deprecated property of a given class

```php
class A {
    /**
     * @deprecated
     * @var ?string
     */
    public $foo;
}
(new A())->foo = 5;
```

### DocblockTypeContradiction

Emitted when conditional is redundant given information supplied in one or more docblocks.

This may be desired (e.g. when checking user input) so is distinct from RedundantCondition, which only applies to non-docblock types.

```php
class A {}

/**
 * @param string $s
 *
 * @return void
 */
function foo($s) {
    if (is_string($s)) {};
}
```

### DuplicateArrayKey

Emitted when an array has a key more than once

```php
$arr = [
    'a' => 1,
    'b' => 2,
    'c' => 3,
    'c' => 4,
];
```

### DuplicateClass

Emitted when a class is defined twice

```php
class A {}
class A {}
```

### DuplicateParam

Emitted when a function has a param defined twice

```php
function foo(int $b, string $b) {}
```

### EmptyArrayAccess

Emitted when attempting to access a value on an empty array

```php
$a = [];
$b = $a[0];
```

### FalsableReturnStatement

Emitted if a return statement contains a false value, but the function return type does not allow false

```php
function foo() : string {
    if (rand(0, 1)) {
        return "foo";
    }

    return false; // emitted here
}
```

### ForbiddenCode

Emitted when Psalm encounters a var_dump, exec or similar expression that may make your code more vulnerable

```php
var_dump($foo);
```

### ImplementedReturnTypeMismatch

Emitted when a class that inherits another, or implements an interface, has docblock return type that's entirely different to the parent. Subclasses of the parent return type are permitted, in docblocks.

```php
class A {
    /** @return bool */
    public function foo() {
        return true;
    }
}
class B extends A {
    /** @return string */
    public function foo()  {
        return "hello";
    }
}
```

### ImplicitToStringCast

Emitted when implictly converting an object with a `__toString` method to a string

```php
class A {
    public function __toString() {
        return "foo";
    }
}

function takesString(string $s) : void {}

takesString(new A);
```

### InaccessibleClassConstant

Emitted when a public/private class constant is not accessible from the calling context

```php
class A {
    protected const FOO = 'FOO';
}
echo A::FOO;
```

### InaccessibleMethod

Emitted when attempting to access a protected/private method from outside its available scope

```php
class A {
    protected function foo() : void {}
}
echo (new A)->foo();
```

### InaccessibleProperty

Emitted when attempting to access a protected/private property from outside its available scope

```php
class A {
    /** @return string */
    protected $foo;
}
echo (new A)->foo;
```

### InvalidArgument

Emitted when a supplied function/method argument is incompatible with the method signature or docblock one.

```php
class A {}
function foo(A $a) : void {}
foo("hello");
```

### InvalidArrayAccess

Emitted when attempting to access an array offset on a value that does not permit it

```php
$arr = 5;
echo $arr[0];
```

### InvalidArrayAssignment

Emitted when attempting to assign a value on a non-array

```php
$arr = 5;
$arr[0] = 3;
```

### InvalidArrayOffset

Emitted when when attempting to access an array using a value that's not a valid offet for that array

```php
$a = [5, 20, 18];
echo $a["hello"];
```

### InvalidCast

Emitted when attempting to cast a value that's not castable

```php
class A {}
$a = new A();
$b = (string)$a;
```

### InvalidCatch

Emitted when trying to catch a class/interface that doesn't extend `Exception` or implement `Throwable`

```php
class A {}
try {
    $worked = true;
}
catch (A $e) {}
```

### InvalidClass

Emitted when referencing a class with the wrong casing

```php
class Foo {}
(new foo());
```

### InvalidClone

Emitted when trying to clone a value that's not cloneable

```php
$a = "hello";
$b = clone $a;
```

### InvalidDocblock

Emitted when there's an error in a docblock type

```php
/** @var array() */
$a = [];
```

### InvalidFalsableReturnType

Emitted when a function can return a nullable value, but its given return type says otherwise

```php
function foo() : string {
    if (rand(0, 1)) {
        return "foo";
    }

    return false;
}
```

### InvalidFunctionCall

Emitted when calling a function on a non-callable variable

```php
$a = 5;
$b = $a();
```

### InvalidGlobal

Emitted when there's a reference to the global keyword where it's not expected

```php
global $e;
```

### InvalidIterator

Emitted when trying to iterate over a value that's not iterable

```php
$a = 5;
foreach ($a as $b) {}
```

### InvalidMethodCall

Emitted when attempting to call a method on a non-object

```php
$a = 5;
$a->foo();
```

### InvalidNullableReturnType

Emitted when a function can return a nullable value, but its given return type says otherwise

```php
function foo() : string {
    if (rand(0, 1)) {
        return "foo";
    }

    return null;
}
```

### InvalidOperand

Emitted when using something as an operand that is unexected

```php
class A {}
echo (new A) . ' ';
```

### InvalidParamDefault

Emitted when a function parameter default clashes with the type Psalm expects the param to be

```php
function foo(int $i = false) : void {}
```

### InvalidPassByReference

Emitted when passing a non-variable to a function that expects a by-ref variable

```php
function foo(array &$arr) : void {}
foo([0, 1, 2]);
```

### InvalidPropertyAssignment

Emitted when attempting to assign a property to a non-object

```php
$a = "foo";
$a->bar = "bar";
```

### InvalidPropertyAssignmentValue

Emitted when attempting to assign a value to a property that cannot contain that type.

```php
class A {
    /** @var string|null */
    public $foo;
}
$a = new A();
$a->foo = new stdClass();
```

### InvalidPropertyFetch

Emitted when attempting to get a property from a non-object

```php
$a = "foo";
echo $a->bar;
```

### InvalidReturnStatement

Emitted when a function return statement is incorrect

```php
function foo() : string {
    return 5; // emitted here
}
```

### InvalidReturnType

    Emitted when a function’s signature return type is incorrect (often emitted with `InvalidReturnStatement`)

```php
function foo() : int {
    if (rand(0, 1)) {
        return "hello";
    }

    return 5;
}
```

### InvalidScalarArgument

Emitted when a scalar value is passed to a method that expected another scalar type

```php
function foo(int $i) : void {}
function bar(string $s) : void {
    if (is_numeric($s)) {
        foo($s);
    }
}
```

### InvalidScope

Emitted when referring to `$this` outside a class

```php
echo $this;
```

### InvalidStaticInvocation

Emitted when trying to call an instance function statically

```php
class A {
    /** @var ?string */
    public $foo;

    public function bar() : void {
        echo $this->foo;
    }
}

A::bar();
```

### InvalidThrow

Emitted when trying to throw a class that doesn't extend `Exception` or implement `Throwable`

```php
class A {}
throw new A();
```

### InvalidToString

Emitted when a `__toString` method does not always return a `string`

```php
class A {
    public function __toString() {
        return true;
    }
}
```

### LessSpecificReturnStatement

Emitted when a return statement is more general than the return type given for the function

```php
class A {}
class B extends A {}

function foo() : B {
    return new A(); // emitted here
}
```

### LessSpecificReturnType

Emitted when a return type covers more possibilities than the function itself

```php
function foo() : ?int {
    return 5;
}
```

### LoopInvalidation

Emitted when logic inside a loop invalidates one of the conditionals of the loop

```php
for ($i = 0; $i < 10; $i++) {
    $i = 5;
}
```

### MethodSignatureMismatch

Emitted when a method parameter differs from a parent method parameter, or if there are fewer parameters than the parent method

```php
class A {
    public function foo(int $i) : void {}
}
class B extends A {
    public function foo(string $s) : void {}
}
```

### MismatchingDocblockParamType

Emitted when an `@param` entry in a function’s docblock doesn’t match the param typehint,

```php
class A {}
class B {}
/**
 * @param B $b // emitted here
 */
function foo(A $b) : void {}
```

This, however, is fine:

```php
class A {}
class B extends A {}
/**
 * @param B
 */
function foo(A $b) : void {}
```

### MismatchingDocblockReturnType

Emitted when an `@return` entry in a function’s docblock doesn’t match the function return typehint

```php
class A {}
class B {}
/**
 * @return B // emitted here
 */
function foo() : A {
    return new A();
}
```

This, however, is fine:

```php
class A {}
class B extends A {}
/**
 * @return B // emitted here
 */
function foo() : A {
    return new B();
}
```

### MismatchingDocblockParamType

Emitted when an `@param` entry in a function’s docblock doesn’t match the param typehint

```php
/**
 * @param int $b
 */
function foo(string $b) : void {}
```

### MisplacedRequiredParam

Emitted when a required param is before a param that is not required. Included in Psalm because it is an E_WARNING in PHP

```php
function foo(int $i = 5, string $j) : void {}
```

### MissingClosureParamType

Emitted when a closure paramter has no type information associated with it

```php
$a = function($a): string {
    return "foo";
};
```

### MissingClosureReturnType

Emitted when a closure lacks a return type

```php
$a = function() {
    return "foo";
};
```

### MissingConstructor

Emitted when non-null properties without default values are defined in a class without a `__construct` method

```php
class A {
    /** @var string */
    public $foo;
}
```

### MissingDocblockType

Emitted when a docblock is present, but the type is missing or badly formatted

```php
/** @var $a */
$a = [];
```

### MissingFile

Emitted when using `include` or `require` on a file that does not exist

```php
require("nonexistent.php");
```

### MissingParamType

Emitted when a function paramter has no type information associated with it

```php
function foo($a) : void {}
```

### MissingPropertyType

Emitted when a property is defined on a class without a type

```php
class A {
    public $foo;
}
```

### MissingReturnType

Emitted when a function doesn't have a return type defined

```php
function foo() {
    return "foo";
}
```

### MixedArgument

Emitted when Psalm cannot determine the type of an argument

```php
function takesInt(int $i) : void {}
takesInt($_GET['foo']);
```

### MixedArrayAccess

Emitted when trying to access an array offset on a value whose type Psalm cannot determine

```php
echo $_GET['foo'][0];
```

### MixedArrayAssignment

Emitted when trying to assign a value to an array offset on a value whose type Psalm cannot determine

```php
$_GET['foo'][0] = "5";
```

### MixedArrayOffset

Emitted when attempting to access an array offset where Psalm cannot determine the offset type

```php
echo [1, 2, 3][$_GET['foo']];
```

### MixedAssignment

Emitted when assigning a variable to a value for which Psalm cannot infer a type

```php
$a = $_GET['foo'];
```

### MixedInferredReturnType

Emitted when Psalm cannot determine a function's return type

```php
function foo() : int {
    return $_GET['foo'];
}
```

### MixedMethodCall

Emitted when calling a method on a value that Psalm cannot infer a type for

```php
/** @param mixed $a */
function foo($a) : void {
    $a->foo();
}
```

### MixedOperand

Emitted when Psalm cannot infer a type for an operand in any calculated expression

```php
echo $_GET['foo'] + "hello";
```

### MixedPropertyAssignment

Emitted when assigning a property to a value for which Psalm cannot infer a type

```php
/** @param mixed $a */
function foo($a) : void {
    $a->foo = "bar";
}
```

### MixedPropertyFetch

Emitted when retrieving a property on a value for which Psalm cannot infer a type

```php
/** @param mixed $a */
function foo($a) : void {
    echo $a->foo;
}
```

### MixedReturnStatement

Emitted when Psalm cannot determine the type of a given return statement

```php
function foo() : int {
    return $_GET['foo']; // emitted here
}
```

### MixedStringOffsetAssignment

Emitted when assigning a value on a string using a value for which Psalm cannot infer a type

```php
"hello"[0] = $_GET['foo'];
```

### MixedTypeCoercion

Emitted when Psalm cannot be sure that part of an array/iterabble argument's type constraints can be fulfilled

```php
function foo(array $a) : void {
    takesStringArray($a);
}

/** @param string[] $a */
function takesStringArray(array $a) : void {}
```

### MoreSpecificImplementedParamType

Emitted when a class implements an interface method but a param type is less specific than the interface method param type

```php
class A {}
class B extends A {
    public function bar(): void {}
}
class C extends A {
    public function bar(): void {}
}

class D {
    public function foo(A $a): void {}
}

class E extends D {
    /** @param B|C $a */
    public function foo(A $a): void {
        $a->bar();
    }
}
```

### MoreSpecificImplementedReturnType

Emitted when a class implements an interface method but its return type is less specific than the interface method return type

```php
class A {}
class B extends A {}
interface I {
    /** @return B[] */
    public function foo();
}
class D implements I {
    /** @return A[] */
    public function foo() {
        return [new A, new A];
    }
}
```

### MoreSpecificReturnType

Emitted when the declared return type for a method is more specific than the inferred one (emitted in the same methods that `LessSpecificReturnStatement` is)

```php
class A {}
class B extends A {}
function foo() : B {
    /** @psalm-suppress LessSpecificReturnStatement */
    return new A();
}
```

### NoInterfaceProperties

Emitted when trying to fetch a property on an interface as interfaces, by definition, do not have definitions for properties.

```php
interface I {}
class A implements I {
    /** @var ?string */
    public $foo;
}
function bar(I $i) : void {
    if ($i->foo) {}
}
```

### NonStaticSelfCall

Emitted when calling a non-static function statically

```php
class A {
    public function foo(): void {}

    public function bar(): void {
        self::foo();
    }
}
```

### NullableReturnStatement

Emitted if a return statement contains a null value, but the function return type is not nullable

```php
function foo() : string {
    if (rand(0, 1)) {
        return "foo";
    }

    return null; // emitted here
}
```

### NullArgument

Emitted when calling a function with a null value argument when the function does not expect it

```php
function foo(string $s) : void {}
foo(null);
```

### NullArrayAccess

Emitted when trying to access an array value on `null`

```php
$arr = null;
echo $arr[0];
```

### NullArrayOffset

Emitted when trying to access an array offset with `null`

```php
$arr = ['' => 5, 'foo' => 1];
echo $arr[null];
```

### NullFunctionCall

Emitted when trying to use `null` as a `callable`

```php
$arr = null;
echo $arr();
```

### NullIterator

Emitted when iterating over `null`

```php
foreach (null as $a) {}
```

### NullOperand

Emitted when using `null` as part of an operation (e.g. `+`, `.`, `^` etc.`)

```php
echo null . 'hello';
```

### NullPropertyAssignment

Emitted when trying to set a property on `null`

```php
$a = null;
$a->foo = "bar";
```

### NullPropertyFetch

Emitted when trying to fetch a property on a `null` value

```php
$a = null;
echo $a->foo;
```

### NullReference

Emitted when attempting to call a method on `null`

```php
$a = null;
$a->foo();
```

### OverriddenMethodAccess

Emitted when a method is less accessible than its parent

```php
class A {
    public function foo() : void {}
}
class B extends A {
    protected function foo() : void {}
}
```

### ParadoxicalCondition

Emitted when a paradox is encountered in your programs logic that could not be caught by `RedundantCondition`

```php
function foo(?string $a) : ?string {
    if ($a) return $a;
    if ($a) echo "cannot happen";
}
```

### ParentNotFound

Emitted when using `parent::` in a class without a parent class.

```php
class A {
  public function foo() : void {
    parent::foo();
  }
}
```

### PossiblyFalseArgument

Emitted when a function argument is possibly `false`, but the function doesn’t expect `false`. This is distinct from a function argument is possibly `bool`, which results in `PossiblyInvalidArgument`.

```php
function foo(string $s) : void {
    $a_pos = strpos($s, "a");
    echo substr($s, $a_pos);
}
```

### PossiblyFalsePropertyAssignmentValue

Emitted when trying to assign a value that may be false to a property that only takes non-false values.

```php
class A {
    /** @var int */
    public $foo = 0;
}

function assignToA(string $s) {
    $a = new A();
    $a->foo = strpos("haystack", $s);
}
```

### PossiblyFalseReference

Emitted when making a method call on a value than might be `false`

```php
class A {
    public function bar() : void {}
}

/** @return A|false */
function foo() {
    return rand(0, 1) ? new A : false;
}

foo()->bar();
```

### PossiblyInvalidArgument

Emitted when

```php
/** @return int|stdClass */
function foo() {
    return rand(0, 1) ? 5 : new stdClass;
}
function bar(int $i) : void {}
bar(foo());
```

### PossiblyInvalidArrayAccess

Emitted when attempting to access an array offset on a value that may not be an array

```php
$arr = rand(0, 1) ? 5 : [4, 3, 2, 1];
echo $arr[0];
```

### PossiblyInvalidArrayAssignment

Emitted when attempting to assign an array offset on a value that may not be an array

```php
$arr = rand(0, 1) ? 5 : [4, 3, 2, 1];
$arr[0] = "hello";
```

### PossiblyInvalidArrayOffset

Emitted when it’s possible that the array offset is not applicable to the value you’re trying to access.

```php
$arr = rand(0, 1) ? ["a" => 5] : "hello";
echo $arr[0];
```

### PossiblyInvalidFunctionCall

Emitted when trying to call a function on a value that may not be callable

```php
$a = rand(0, 1) ? 5 : function() : int { return 5; };
$b = $a();
```

### PossiblyInvalidMethodCall

Emitted when trying to call a method on a value that may not be an object

```php
class A {
    public function bar() : void {}
}

/** @return A|int */
function foo() {
    return rand(0, 1) ? new A : 5;
}

foo()->bar();
```

### PossiblyInvalidPropertyAssignment

Emitted when trying to assign a property on a value that may not be an object or may be an object that doesn’t have the desired property.

```php
class A {
    /** @var ?string */
    public $bar;
}

/** @return A|int */
function foo() {
    return rand(0, 1) ? new A : 5;
}

$a = foo();
$a->bar = "5";
```

### PossiblyInvalidPropertyAssignmentValue

Emitted when trying to assign a possibly invalid value to a typed property.

```php
class A {
    /** @var int[] */
    public $bb = [];
}

class B {
    /** @var string[] */
    public $bb;
}

$c = rand(0, 1) ? new A : new B;
$c->bb = ["hello", "world"];
```

### PossiblyInvalidPropertyFetch

Emitted when trying to fetch a property on a value that may not be an object or may be an object that doesn’t have the desired property.

```php
class A {
    /** @var ?string */
    public $bar;
}

/** @return A|int */
function foo() {
    return rand(0, 1) ? new A : 5;
}

$a = foo();
echo $a->bar;
```

### PossiblyNullArgument

Emitted when calling a function with a value that’s possibly null when the function does not expect it

```php
function foo(string $s) : void {}
foo(rand(0, 1) ? "hello" : null);
```

### PossiblyNullArrayAccess

Emitted when trying to access an array offset on a possibly null value

```php
function foo(?array $a) : void {
    echo $a[0];
}
```

### PossiblyNullArrayAssignment

Emitted when trying to set a value on a possibly null array

```php
$a = null;
$a[0][] = 1;
```

### PossiblyNullArrayOffset

Emitted when trying to access a value on an array using a possibly null offset

```php
function foo(?int $a) : void {
    echo [1, 2, 3, 4][$a];
}
```

### PossiblyNullFunctionCall

Emitted when trying to call a function on a value that may be null

```php
function foo(?callable $a) : void {
    $a();
}
```

### PossiblyNullIterator

Emitted when trying to iterate over a value that may be null

```php
function foo(?array $arr) : void {
    foreach ($arr as $a) {}
}
```

### PossiblyNullOperand

Emitted when using a possibly `null` value as part of an operation (e.g. `+`, `.`, `^` etc.`)

```php
function foo(?int $a) : void {
    echo $a + 5;
}
```

### PossiblyNullPropertyAssignment

Emitted when trying to assign a property to a possibly null object

```php
class A {
    /** @var ?string */
    public $foo;
}
function foo(?A $a) : void {
    $a->foo = "bar";
}
```

### PossiblyNullPropertyAssignmentValue

Emitted when trying to assign a value that may be null to a property that only takes non-null values.

```php
class A {
    /** @var string */
    public $foo = "bar";
}

function assignToA(?string $s) {
    $a = new A();
    $a->foo = $s;
}
```

### PossiblyNullPropertyFetch

Emitted when trying to fetch a property on a possibly null object

```php
class A {
    /** @var ?string */
    public $foo;
}
function foo(?A $a) : void {
    echo $a->foo;
}
```

### PossiblyNullReference

Emitted when trying to call a method on a possibly null value

```php
class A {
    public function bar() : void {}
}
function foo(?A $a) : void {
    $a->bar();
}
```

### PossiblyUndefinedGlobalVariable

Emitted when trying to access a variable in the global scope that may not be defined

```php
if (rand(0, 1)) {
  $a = 5;
}
echo $a;
```

### PossiblyUndefinedMethod

Emitted when trying to access a method that may not be defined on the object

```php
class A {
    public function bar() : void {}
}
class B {}

$a = rand(0, 1) ? new A : new B;
$a->bar();
```

### PossiblyUndefinedVariable

Emitted when trying to access a variable in function scope that may not be defined

```php
function foo() : void {
    if (rand(0, 1)) {
        $a = 5;
    }
    echo $a;
}
```

### PossiblyUnusedMethod

Emitted when `--find-dead-code` is turned on and Psalm cannot find any calls to a given class method

```php
class A {
    public function foo() : void {}
    public function bar() : void {}
}
(new A)->foo();
```

### PossiblyUnusedParam

Emitted when `--find-dead-code` is turned on and Psalm cannot find any uses of a particular parameter in a public/protected method

```php
class A {
    public function foo(int $a, int $b) : int {
        return $a + 4;
    }
}

$a = new A();
$a->foo(1, 2);
```

### PossiblyUnusedProperty

Emitted when `--find-dead-code` is turned on and Psalm cannot find any uses of a particular public/protected property

```php
class A {
    /** @var string|null */
    public $foo;

    /** @var int|null */
    public $bar;
}

$a = new A();
echo $a->foo;
```

### PropertyNotSetInConstructor

Emitted when a non-null property without a default value is declared but not set in the class’s constructor

```php
class A {
    /** @var string */
    public $foo;

    public function __construct() {}
}
```

### RawObjectIteration

Emitted when iterating over an object’s properties. This issue exists because it may be undesired behaviour (e.g. you may have meant to iterate over an array)

```php
class A {
    /** @var string|null */
    public $foo;

    /** @var string|null */
    public $bar;
}

function takesA(A $a) {
    foreach ($a as $property) {}
}
```

### RedundantCondition

Emitted when conditional is redundant given previous assertions

```php
class A {}
function foo(?A $a) : ?A {
    if ($a) return $a;
    if ($a) echo "cannot happen";
}
```

### ReferenceConstraintViolation

Emitted when changing the type of a pass-by-reference variable

```php
function foo(string &$a) {
    $a = 5;
}
```

### ReservedWord

Emitted when using a reserved word as a class name

```php
function foo(resource $res) : void {}
```

### TooFewArguments

Emitted when calling a function with fewer arguments than the function has parameters

```php
function foo(string $a) : void {}
foo();
```

### TooManyArguments

Emitted when calling a function with more arguments than the function has parameters

```php
function foo(string $a) : void {}
foo("hello", 4);
```

### TypeCoercion

Emitted when calling a function with an argument which has a less specific type than the function expects

```php
class A {}
class B extends A {}

function takesA(A $a) : void {
    takesB($a);
}
function takesB(B $b) : void {}
```

### TypeDoesNotContainNull

Emitted when checking a non-nullable type for `null`

```php
$a = "hello";
if ($a === null) {}
```

### TypeDoesNotContainType

Emitted checking whether one value has a type or value that is impossible given its currently-known type

```php
$a = "hello";
if ($a === 5) {}
```

### UndefinedClass

Emitted when referencing a class that doesn’t exist

```php
$a = new A();
```

### UndefinedConstant

Emitted when referencing a constant that doesn’t exist

```php
echo FOO_BAR;
```

### UndefinedFunction

Emitted when referencing a function that doesn't exist

```php
foo();
```

### UndefinedGlobalVariable

Emitted when referencing a variable that doesn't exist

```php
echo $a;
```

### UndefinedMethod

Emitted when calling a method that doesn’t exist

```php
class A {}
A::foo();
```

### UndefinedPropertyAssignment

Emitted when assigning a property on an object that doesn’t have that property defined

```php
class A {}
$a = new A();
$a->foo = "bar";
```

### UndefinedPropertyFetch

Emitted when getting a property on an object that doesn’t have that property defined

```php
class A {}
$a = new A();
echo $a->foo;
```

### UndefinedThisPropertyAssignment

Emitted when assigning a property on an object in one of that object’s methods when no such property exists

```php
class A {
    function foo() {
        $this->foo = "bar";
    }
}
```

### UndefinedThisPropertyFetch

Emitted when getting a property for an object in one of that object’s methods when no such property exists

```php
class A {
    function foo() {
        echo $this->foo;
    }
}
```

### UndefinedTrait

Emitted when referencing a trait that doesn’t exist

```php
class A {
    use T;
}
```

### UndefinedVariable

Emitted when referencing a variable that doesn't exist in a given function’s scope

```php
function foo() {
    echo $a;
}
```

### UnevaluatedCode

Emitted when `--find-dead-code` is turned on and Psalm encounters code that will not be evaluated

```php
function foo() : void {
    return;
    $a = "foo";
}
```

### UnimplementedAbstractMethod

Emitted when a class extends another, but does not implement all of its abstract methods

```php
abstract class A {
    abstract public function foo();
}
class B extends A {}
```

### UnimplementedInterfaceMethod

Emitted when a class `implements` an interface but does not implement all of its methods

```php
interface I {
    public function foo();
}
class A implements I {}
```

### UnrecognizedExpression

Emitted when Psalm encounters an expression that it doesn't know how to handle. This should never happen.

### UnrecognizedStatement

Emitted when Psalm encounters a code construct that it doesn't know how to handle. This should never happen.

### UnresolvableInclude

Emitted when Psalm cannot figure out what specific file is being included/required by PHP.

```php
function requireFile(string $s) : void {
    require_once($s);
}
```

### UnusedClass

Emitted when `--find-dead-code` is turned on and Psalm cannot find any uses of a given class

```php
class A {}
class B {}
$a = new A();
```

### UnusedMethod

Emitted when `--find-dead-code` is turned on and Psalm cannot find any uses of a given private method or function

```php
class A {
    public function __construct() {
        $this->foo();
    }
    private function foo() : void {}
    private function bar() : void {}
}
$a = new A();
```

### UnusedParam

Emitted when `--find-dead-code` is turned on and Psalm cannot find any uses of a particular parameter in a private method or function

```php
function foo(int $a, int $b) : int {
    return $a + 4;
}
```

### UnusedProperty

Emitted when `--find-dead-code` is turned on and Psalm cannot find any uses of a private property

```php
class A {
    /** @var string|null */
    private $foo;

    /** @var int|null */
    private $bar;

    public function getFoo(): ?string {
        return $this->foo;
    }
}

$a = new A();
echo $a->getFoo();
```

### UnusedVariable

Emitted when `--find-dead-code` is turned on and Psalm cannot find any references to a variable, once instantiated

```php
function foo() : void {
    $a = 5;
    $b = 4;
    echo $b;
}
```

