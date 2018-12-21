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
}

class_alias("ClassAliasStubTest\\A", "ClassAliasStubTest\\B");
class_alias(A::class, C::class);

$arr = [
    [A::class, D::class]
];

// Psalm cannot reason about this in the loading step
class_alias($arr[0][0], $arr[0][1]);
