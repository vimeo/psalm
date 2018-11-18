Version 3.0.0 (2018-11-18)

- Refactored a lot of classes to support an overhauled [Plugin API](https://github.com/vimeo/psalm/blob/master/docs/plugins.md) created by @weirdan 
- Config `allowCoercionFromStringToClassConst` now defaults to `false`, meaning Psalm now finds a problem with this code by default:
    ```php
    $a = "A";
    new $a(); // InvalidStringClass emitted
    $a = A::class;
    new $a(); // this is fine
    ```
- Disabled PHP's cycle-detecting garbage collector (which is often run unnecessarily)
- Improved docblock assertions to allow templated types e.g
    ```php
    /**
     * Asserts that two variables are the same.
     *
     * @psalm-template T
     * @psalm-param T      $expected
     * @param mixed  $actual
     * @psalm-assert !=T $actual
     */
    function assertNotSame($expected, $actual) : void {}
    ```

Version 2.0.0 (2018-05-11)

- Uses PHP Parser 4 (and thus requires PHP 7)
- Issue type `MoreSpecificImplementedReturnType` has been renamed `LessSpecificImplementedReturnType`
- Issue type `PossiblyUndefinedArrayOffset` is triggered for possibly undefined array keys (previously bucketed into `PossiblyUndefinedVariable`)
    ```php
    $foo = rand(0, 1) ? ['a' => 1, 'b' => 2] : ['a' => 3];
    echo $foo['b'];
    ```
- removed `stopOnFirstError` `<psalm />` config attribute, which hasn't been used in ages
- removed `UntypedParam` issue type, which also hasn't been used (`MissingParamType` is the replacement)

