# Plugins: type system internals

Psalm's type system represents the types of variables within a program using different classes. Plugins both receive, and can update this type information.

## Union types

All type information you are likely to use will be wrapped in a [Union Type](../../annotating_code/type_syntax/union_types.md).

The `Union` class constructor takes an array of `Atomic` types, and can represent one or more of these types at a time. They correspond to a vertical bar in a doc comment.

``` php
new Union([new TNamedObject('Foo\\Bar\\SomeClass')]); // equivalent to Foo\Bar\SomeClass in docblock
new Union([new TString(), new TInt()]); // equivalent to string|int in docblock
```

## Atomic types

Primitive types like floats, integers and strings, plus arrays, and classes. You can find all of these in [`src/Psalm/Types/Atomic`](https://github.com/vimeo/psalm/tree/master/src/Psalm/Type/Atomic).

Note that all non-abstract classes in this folder are valid types. They are all prefixed by 'T'.

The classes are as follows:

### Misc

`TVoid` - denotes the `void` type, normally just used to annotate a function/method that returns nothing

`TNull` - denotes the `null` type

`TNever` - denotes the `no-return`/`never-return` type for functions that never return, either throwing an exception or terminating (like the builtin `exit()`). Also used for union types that can have no possible types (impossible intersections for example).  Empty arrays `[]` have the type `array<never, never>`.

`TMixed` - denotes the `mixed` type, used when you don’t know the type of an expression.

`TNonEmptyMixed `- as above, but not empty. Generated for `$x` inside the `if` statement `if ($x) {...}` when `$x` is `mixed` outside.

`TEmptyMixed` - as above, but empty. Generated for `$x` inside the `if` statement `if (!$x) {...}` when `$x` is `mixed` outside.

`TIterable` - denotes the [`iterable` type](https://www.php.net/manual/en/language.types.iterable.php) (which can also result from an `is_iterable` check).

`TResource` - denotes the `resource` type (e.g. a file handle).

`TClosedResource` - denotes the `resource` type that has been closed (e.g. a file handle through `fclose()`).

`TAssertionFalsy` - Represents any value reduced to false when computed in boolean context. This is used for assertions

`TConditional` - Internal representation of a conditional return type in phpdoc. For example ($param1 is int ? int : string)

`TIntMask` - Represents the type that is the result of a bitmask combination of its parameters. `int-mask<1, 2, 4>` corresponds to `1|2|3|4|5|6|7`

`TIntMaskOf` - as above, but used with a reference to constants in code `int-mask-of<MyClass::CLASS_CONSTANT_*>` will corresponds to `1|2|3|4|5|6|7` if there are three constant 1, 2 and 4

`TKeyOf` - Represents an offset of an array (e.g. `key-of<MyClass::CLASS_CONSTANT>`).

`TValueOf` - Represents a value of an array or enum (e.g. `value-of<MyClass::CLASS_CONSTANT>`).

`TTemplateIndexedAccess` - To be documented

`TTemplateKeyOf` - Represents the type used when using TKeyOf when the type of the array is a template

`TTemplateValueOf` - Represents the type used when using TValueOf when the type of the array or enum is a template

`TPropertiesOf` - Represents properties and their types of a class as a keyed array (e.g. `properties-of<MyClass>`)

`TTemplatePropertiesOf` - Represents the type used when using TPropertiesOf when type of the class is a template

`TTypeAlias` - To be documented

### Scalar supertype

`TScalar` - denotes the `scalar` super type (which can also result from an `is_scalar` check). This type encompasses `float`, `int`, `bool` and `string`.

`TEmptyScalar` - denotes a `scalar` type that is also empty.

`TNonEmptyScalar` - denotes a `scalar` type that is also non-empty.

### Numeric supertype

`TNumeric` - denotes the `numeric` type (which can also result from an `is_numeric` check).

`TEmptyNumeric` - denotes the `numeric` type that's also empty (which can also result from an `is_numeric` and `empty` check).

### Scalar types

All scalar types have literal versions e.g. `int` vs `int(5)`.

#### Ints

`TInt` - denotes the `int` type, where the exact value is unknown.

`TLiteralInt` - is used to represent an integer value where the exact numeric value is known.

`TIntRange` - allows to describe an int with bounded values (ie. `int<1, 5>`).

#### Floats

`TFloat` - denotes the `float` type, where the exact value is unknown.

`TLiteralFloat` - is used to represent a floating point value where the exact numeric value is known.

#### Bools

`TBool`, `TFalse`, `TTrue`

`TBool` - denotes the `bool` type where the exact value is unknown.

`TFalse` - denotes the `false` value type

`TTrue` - denotes the `true` value type

``` php
/** @return string|false    false when string is empty, first char of the parameter otherwise */
function firstChar(string $s) { return empty($s) ? false : $s[0]; }
```

Here, the function may never return true, but if you had to replace false with bool, Psalm would have to consider true as a possible return value. With narrower type it's able to report meaningless code like this (https://psalm.dev/r/037291351d):

``` php
$first = firstChar("sdf");
if (true === $first) {
  echo "This is actually dead code";
}
```

#### Strings

`TString` - denotes the `string` type, where the exact value is unknown.

`TNonEmptyString` - denotes a string, that is also non-empty

`TNumericString` - denotes a string that's also a numeric value e.g. `"5"`. It can result from `is_string($s) && is_numeric($s)`.

`TLiteralString` - is used to represent a string whose value is known.

`TClassString` - denotes the `class-string` type, used to describe a string representing a valid PHP class. The parent type from which the classes descend may or may not be specified in the constructor.

`TLiteralClassString` - denotes a specific class string, generated by expressions like `A::class`.

`TTraitString` - denotes the `trait-string` type, used to describe a string representing a valid PHP trait.

`TDependentGetClass` - Represents a string whose value is a fully-qualified class found by get_class($var)

`TDependentGetDebugType` - Represents a string whose value is that of a type found by get_debug_type($var)

`TDependentGetType` - Represents a string whose value is that of a type found by gettype($var)

`TCallableString` - denotes the `callable-string` type, used to represent an unknown string that is also `callable`.

`TSqlSelectString` - this is a special type, specifically for consumption by plugins.

`TLowercaseString` - denotes a string where every character is lowercased. (which can also result from a `strtolower` call).

`TNonEmptyLowercaseString` - denotes a non-empty-string where every character is lowercased. (which can also result from a `strtolower` call).

`TSingleLetter` - denotes a string that has a length of 1

#### Scalar class constants

`TScalarClassConstant` - denotes a class constant whose value might not yet be known.

#### Array key supertype

`TArrayKey` - denotes the `array-key` type, used for something that could be the offset of an `array`.

### Arrays

`TArray` - denotes a simple array of the form `array<TKey, TValue>`. It expects an array with two elements, both union types.

`TNonEmptyArray` - as above, but denotes an array known to be non-empty.

`TKeyedArray` represents an 'object-like array' - an array with known keys.

``` php
$x = ["a" => 1, "b" => 2]; // is TKeyedArray, array{a: int, b: int}
$y = rand(0, 1) ? ["a" => null] : ["a" => 1, "b" => "b"]; // is TKeyedArray with optional keys/values, array{a: ?int, b?: string}
```

This type is also used to represent lists (instead of the now-deprecated `TList` type).  

Note that not all associative arrays are considered object-like. If the keys are not known, the array is treated as a mapping between two types.

``` php
$a = [];
foreach (range(1,1) as $_) $a[(string)rand(0,1)] = rand(0,1); // array<string,int>
```

`TCallableArray` - denotes an array that is _also_ `callable`.

`TCallableKeyedArray` - denotes an object-like array that is _also_ `callable`.

`TClassStringMap` - Represents an array where the type of each value is a function of its string key value

### Callables & closures

`TCallable` - denotes the `callable` type. Can result from an `is_callable` check.
`TClosure` - denotes a `Closure` type.

`TCallable` and `TClosure` can optionally be defined with parameters and return types, too

### Object supertypes

`TObject` - denotes the `object` type

`TObjectWithProperties` - an object with specified member variables e.g. `object{foo:int, bar:string}`.

### Object types

`TNamedObject` - denotes an object type where the type of the object is known e.g. `Exception`, `Throwable`, `Foo\Bar`

`TGenericObject` - denotes an object type that has generic parameters e.g. `ArrayObject<string, Foo\Bar>`

`TCallableObject` - denotes an object that is also `callable` (i.e. it has `__invoke` defined).

`TAnonymousClassInstance` - Denotes an anonymous class (i.e. `new class{}`) with potential methods

### Template

`TTemplateParam` - denotes a template parameter that has been previously specified in a `@template` tag.

`TTemplateParamClass` - denotes a `class-string` corresponding to a template parameter previously specified in a `@template` tag.

## Creating type object instances

There are two ways of creating the object instances which describe a given type. They can be created directly using new, or created declaratively from a doc string. Normally, you'd want to use the second option. However, understanding the structure of this data will help you understand types passed into a plugin.

Note that these classes do sometimes change, so `Type::parseString` is always going to be the more robust option.

### Creating type object instances directly

The following example constructs types representing a string, a floating-point number, and a class called 'Foo\Bar\SomeClass'.

``` php
new TLiteralString('A text string')
new TLiteralFloat(3.142)
new TNamedObject('Foo\Bar\SomeClass')
```

Types within Psalm are always wrapped in a union as a convenience feature. Almost anywhere you may expect a type, you can get a union as well (property types, return types, argument types, etc). So wrapping a single atomic type (like TInt) in a union container allows to uniformly handle that type elsewhere, without repetitive checks like this:

``` php
if ($type instanceof Union)
   foreach ($types->getTypes() as $atomic)
      handleAtomic($atomic);
else handleAtomic($type);

// with union container it becomes
foreach ($types->getTypes() as $atomic)
   handleAtomic($atomic);
```

Also, union trees are always shallow, because Psalm will flatten union of unions into a single-level union `((A|B)|(C|D) => A|B|C|D)`.

More complex types can be constructed as follows. The following represents an associative array with 3 keys. Psalm calls these 'object-like arrays', and represents them with the 'TKeyedArray' class.


``` php
        new Union([
            new TKeyedArray([
                'key_1' => new Union([new TString()]),
                'key_2' => new Union([new TInt()]),
                'key_3' => new Union([new TBool()])])]);
```

The Type object includes some static helper methods, which automatically wrap the type in a Union. Thus this can be written more tersely:

``` php
new Union([
    new Type\Atomic\TKeyedArray([
        'first' => Type::getInt(),
        'second' => Type::getString()])]);
```

You can also use `Type::getInt(5)` to generate a union type corresponding to the literal int value 5.


### Creating type object instances from doc string types

Another way of creating these instances is to use the class `Psalm\Type` which includes a static method `parseString`. You may pass any doc string type description to this, and it will return the corresponding object representation.

``` php
\Psalm\Type::parseString('int|null');
```

You can find how Psalm would represent a given type as objects, by specifying the type as an input to this function, and calling `var_dump` on the result.
