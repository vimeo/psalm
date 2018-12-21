<?php

namespace ClassAliasStubTest;

class A {
    /**
     * @var string
     */
    public $foo = "hello";

    public function bar(string $s) : string {
        return $s . " I’m here";
    }

    public static function bat() : void {}
}

interface I {}

class_alias("ClassAliasStubTest\\A", "ClassAliasStubTest\\B");
class_alias(A::class, C::class);

$arr = [
    [A::class, D::class],
    [I::class, IAlias::class],
];

foreach ($arr as list($orig, $alias)) {
    // Psalm cannot reason about this in the loading step
    class_alias($orig, $alias);
}
