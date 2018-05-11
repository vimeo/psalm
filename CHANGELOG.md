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
