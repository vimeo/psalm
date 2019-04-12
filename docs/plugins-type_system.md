# Plugins: type system internals

Psalm's type system represents the types of variables within a program using different classes. Primitive types like floats, integers and strings, plus arrays, and classes. You can find all of these in 'src/Psalm/Types/Atomic'. Plugins both receive, and can update this type information.

Note that all classes, but not traits in this directory are valid types. Most, but not all, are prefixed 'T'. This will be changing in version 4, all of them will be prefixed, and a few names will be changed for clarity.


# Union

Class Union specifies a set of types, and can represent any one of these types at a time. They correspond to a vertical bar in a doc comment.

``` php
new Union([new TNamedObject('some_class')]);
new Union([new TString(),
           new TInt()]);

```


### Misc

TVoid, TNull

TNever - for functions that never return, either throwing an exception or terminating like the builtin exit().

TMixed - A variable whose exact type is unknown, and can hold any type.
TNonEmptyMixed - The same as above, but not empty


TIterable - An iterable
TResource - A resource, such as a file handle.


### Scalar supertype

TScalar


### Numeric supertype

TNumeric


### Int

TInt, TLiteralInt

Some types have two specialisations, for example TInt and TLiteralInt. The first variant refers to the type integer, where the exact value is unknown. Such as an integer parameter to a function.

``` php
function foo(int $bar): void {}
```

TLiteralInt is used to represent an integer variable where the exact value is known. This is the case when a function is called, or an integer is assigned to a variable.

``` php
foo(42);
$baz = 4567;
```


### Float

TFloat, TLiteralFloat


### Strings

TString, TLiteralString, TNumericString, TSingleLetter

and TString is your ordinary string containing some arbitrary bytes.


### Array

TArray, TNonEmptyArray, ObjectLike, TArrayKey


The class ObjectLike, represents an 'object-like array'. An array with known keys.

``` php
$a = ["a" => 1, "b" => 2]; // is ObjectLike, array{a:int(1),b:int(2)}
```

Note that not all assosiative arrays are considered object-like. If the keys are not known, the array is treated as a mapping between two types.

``` php
$a = [];
foreach (range(1,1) as $_) $a[(string)rand(0,1)] = rand(0,1); // array<string,int>
```


### Bool

TBool, TFalse, TTrue

True and false have dedicated types as a convenience for functions which return a union of a type, and one of true or false. 

``` php
/** @return false|string false when string is empty, first char of the parameter otherwise */
function firstChar(string $s) { return empty($s) ? false : $s[0]; }
```

Here, the function may never return true, but if you had to replace false with bool, Psalm would have to consider true as a possible return value. With narrower type it's able to report meaningless code like this (https://psalm.dev/r/037291351d):

``` php
$first = firstChar("sdf");
if (true === $first) {
  echo "This is actually dead code";
}
```


### Callables

TCallable, TCallableArray, TCallableObject, TCallableObjectLikeArray, TCallableString

These represent PHP's different callables.


### Empty

TEmpty, TEmptyMixed, TEmptyScalar


### Class

TClassString, TLiteralClassString, TScalarClassConstant

TClassString represents valid (but not necessarily known) class name. TLiteralClassString is a known class name.


### Function

Fn


### Object

TObject, TGenericObject - Arbitrary objects.
TNamedObject -  an instance of a specific object.
TObjectWithProperties - an object with specified member variables. object{foo:int, bar:string}.


### Template

TTemplateParam, TTemplateParamClass


### Special

THtmlEscapedString, TSqlSelectString

These are special types, specifically for consumption by plugins.


## Creating type object instances

There are two ways of creating the object instances which describe a given type. They can be created directly using new, or created declaratively from a doc string. Normally, you'd want to use the second option. Howeaver, understanding the structure of this data will help you understand types passed into a plugin.
Note that these classes do sometimes change, so Type::parseString is always going to be the more robust option.


### Creating type object instances directly

The following example constructs a types representing a string, a floating point number, and a class called 'some\_class'.

``` php
new TLiteralString('A text string')
new TLiteralFloat(3.142)
new TNamedObject('some_class')
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

Also, union trees are always shallow, because Psalm will flatten union of unions into a single-level union ((A|B)|(C|D) => A|B|C|D).


More complex types can be constructed as follows. The following represents an assosiative array with 3 keys. Psalm calls these 'object-like arrays', and represents them with the 'ObjectLike' class. This name will be changed to TObjectLikeArray in Psalm 4 to improve clarity.


``` php
        new Union([
            new ObjectLike([
                'key_1' => new Union([new TString()]),
                'key_2' => new Union([new TInt()]),
                'key_3' => new Union([new TBool()])])]);
```

The Type object includes some static helper methods, which automatically wrap the type in a Union. Thus this can be written more tersely: 

``` php
new Union([
    new Type\Atomic\ObjectLike([
        'first' => Type::getInt(),
        'second' => Type::getString()])]);
```

You can also use Type::getInt(5) to generate a union type corresponding to the literal int value 5.


### Creating type object instances from doc string types

Another way of creating these instances is to use the class \Psalm\Type which includes a static method 'parseString'. You may pass any doc string type description to this, and it will return the corresponding object representation.

``` php
\Psalm\Type::parseString('int|null');
```

You can find how psalm would represent a given type as objects, by specifying the type as an input to this function, and calling var\_dump on the result.


