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

### ContinueOutsideLoop

Emitted when encountering a `continue` statement outside a loop context.

### DeprecatedClass

Emitted when creating a new instance of a deprecated class:

```php
/** @deprecated */
class A {}
new A();
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

### DuplicateClass

Emitted when a class is defined twice

```php
class A {}
class A {}
```

### DuplicateParam

Emitted when a class param is defined twice

```php
class A {
    /** @var ?string */
    public $foo;
    /** @var ?string */
    public $foo;
}
```

### EmptyArrayAccess

Emitted when attempting to access a value on an empty array

```php
$a = [];
$b = $a[0];
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
        return true;
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

    Emitted when a function’s return type is incorrect (often emitted with `InvalidReturnStatement`)

```php
function foo() : string {
    if (rand(0, 1)) {
        return "foo";
    }
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

### MisplacedRequiredParam

Emitted when a required param is before a param that is not required. Included in Psalm because it is an E_WARNING in PHP

```php
function foo(int $i = 5, string $j) : void {}
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
takesInt($_COOKIE['foo']);
```

### MixedArrayAccess

Emitted when trying to access an array offset on a value whose type Psalm cannot determine

```php
echo $_COOKIE['foo'][0];
```

### MixedArrayAssignment

Emitted when trying to assign a value to an array offset on a value whose type Psalm cannot determine

```php
$_COOKIE['foo'][0] = "5";
```

### MixedArrayOffset

Emitted when attempting to access an array offset where Psalm cannot determine the offset type

```php
echo [1, 2, 3][$_COOKIE['foo']];
```

### MixedAssignment

Emitted when

```php
$a = $_COOKIE['foo'];
```

### MixedInferredReturnType

Emitted when

```php

```

### MixedMethodCall

Emitted when

```php

```

### MixedOperand

Emitted when

```php

```

### MixedPropertyAssignment

Emitted when

```php

```

### MixedPropertyFetch

Emitted when

```php

```

### MixedStringOffsetAssignment

Emitted when

```php

```

### MixedTypeCoercion

Emitted when

```php

```

### MoreSpecificImplementedReturnType

Emitted when

```php

```

### MoreSpecificReturnType

Emitted when

```php

```

### NoInterfaceProperties

Emitted when

```php

```

### NonStaticSelfCall

Emitted when

```php

```

### NullArgument

Emitted when

```php

```

### NullArrayAccess

Emitted when

```php

```

### NullArrayOffset

Emitted when

```php

```

### NullFunctionCall

Emitted when

```php

```

### NullIterator

Emitted when

```php

```

### NullOperand

Emitted when

```php

```

### NullPropertyAssignment

Emitted when

```php

```

### NullPropertyFetch

Emitted when

```php

```

### NullReference

Emitted when

```php

```

### OverriddenMethodAccess

Emitted when

```php

```

### ParadoxicalCondition

Emitted when

```php

```

### ParentNotFound

Emitted when

```php

```

### PossiblyFalseArgument

Emitted when

```php

```

### PossiblyFalseReference

Emitted when

```php

```

### PossiblyInvalidArgument

Emitted when

```php

```

### PossiblyInvalidArrayAccess

Emitted when

```php

```

### PossiblyInvalidArrayAssignment

Emitted when

```php

```

### PossiblyInvalidArrayOffset

Emitted when

```php

```

### PossiblyInvalidMethodCall

Emitted when

```php

```

### PossiblyInvalidPropertyAssignment

Emitted when

```php

```

### PossiblyInvalidPropertyFetch

Emitted when

```php

```

### PossiblyNullArgument

Emitted when

```php

```

### PossiblyNullArrayAccess

Emitted when

```php

```

### PossiblyNullArrayAssignment

Emitted when

```php

```

### PossiblyNullArrayOffset

Emitted when

```php

```

### PossiblyNullFunctionCall

Emitted when

```php

```

### PossiblyNullIterator

Emitted when

```php

```

### PossiblyNullOperand

Emitted when

```php

```

### PossiblyNullPropertyAssignment

Emitted when

```php

```

### PossiblyNullPropertyFetch

Emitted when

```php

```

### PossiblyNullReference

Emitted when

```php

```

### PossiblyUndefinedGlobalVariable

Emitted when

```php

```

### PossiblyUndefinedMethod

Emitted when

```php

```

### PossiblyUndefinedVariable

Emitted when

```php

```

### PossiblyUnusedMethod

Emitted when

```php

```

### PossiblyUnusedVariable

Emitted when

```php

```

### PropertyNotSetInConstructor

Emitted when

```php

```

### RawObjectIteration

Emitted when

```php

```

### RedundantCondition

Emitted when

```php

```

### ReferenceConstraintViolation

Emitted when

```php

```

### ReservedWord

Emitted when

```php

```

### TooFewArguments

Emitted when

```php

```

### TooManyArguments

Emitted when

```php

```

### TypeCoercion

Emitted when

```php

```

### TypeDoesNotContainNull

Emitted when

```php

```

### TypeDoesNotContainType

Emitted when

```php

```

### UndefinedClass

Emitted when

```php

```

### UndefinedConstant

Emitted when

```php

```

### UndefinedFunction

Emitted when

```php

```

### UndefinedGlobalVariable

Emitted when

```php

```

### UndefinedMethod

Emitted when

```php

```

### UndefinedPropertyAssignment

Emitted when

```php

```

### UndefinedPropertyFetch

Emitted when

```php

```

### UndefinedThisPropertyAssignment

Emitted when

```php

```

### UndefinedThisPropertyFetch

Emitted when

```php

```

### UndefinedTrait

Emitted when

```php

```

### UndefinedVariable

Emitted when

```php

```

### UnevaluatedCode

Emitted when

```php

```

### UnimplementedAbstractMethod

Emitted when

```php

```

### UnimplementedInterfaceMethod

Emitted when

```php

```

### UnrecognizedExpression

Emitted when

```php

```

### UnrecognizedStatement

Emitted when

```php

```

### UnresolvableInclude

Emitted when

```php

```

### UntypedParam

Emitted when

```php

```

### UnusedClass

Emitted when

```php

```

### UnusedMethod

Emitted when

```php

```

### UnusedVariable

Emitted when

```php

```

