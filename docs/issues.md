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

Emitted when

```php

```

### InaccessibleProperty

Emitted when

```php

```

### InvalidArgument

Emitted when

```php

```

### InvalidArrayAccess

Emitted when

```php

```

### InvalidArrayAssignment

Emitted when

```php

```

### InvalidArrayOffset

Emitted when

```php

```

### InvalidCast

Emitted when

```php

```

### InvalidClass

Emitted when

```php

```

### InvalidClone

Emitted when

```php

```

### InvalidDocblock

Emitted when

```php

```

### InvalidFunctionCall

Emitted when

```php

```

### InvalidGlobal

Emitted when

```php

```

### InvalidIterator

Emitted when

```php

```

### InvalidMethodCall

Emitted when

```php

```

### InvalidOperand

Emitted when

```php

```

### InvalidParamDefault

Emitted when

```php

```

### InvalidPassByReference

Emitted when

```php

```

### InvalidPropertyAssignment

Emitted when

```php

```

### InvalidPropertyFetch

Emitted when

```php

```

### InvalidReturnStatement

Emitted when

```php

```

### InvalidReturnType

Emitted when

```php

```

### InvalidScalarArgument

Emitted when

```php

```

### InvalidScope

Emitted when

```php

```

### InvalidStaticInvocation

Emitted when

```php

```

### InvalidStaticVariable

Emitted when

```php

```

### InvalidToString

Emitted when

```php

```

### LessSpecificReturnStatement

Emitted when

```php

```

### LessSpecificReturnType

Emitted when

```php

```

### MethodSignatureMismatch

Emitted when

```php

```

### MisplacedRequiredParam

Emitted when

```php

```

### MissingClosureReturnType

Emitted when

```php

```

### MissingConstructor

Emitted when

```php

```

### MissingDocblockType

Emitted when

```php

```

### MissingFile

Emitted when

```php

```

### MissingPropertyType

Emitted when

```php

```

### MissingReturnType

Emitted when

```php

```

### MixedArgument

Emitted when

```php

```

### MixedArrayAccess

Emitted when

```php

```

### MixedArrayAssignment

Emitted when

```php

```

### MixedArrayOffset

Emitted when

```php

```

### MixedAssignment

Emitted when

```php

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

