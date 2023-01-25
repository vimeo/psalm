<?php

declare(strict_types=1);

namespace Psalm\Tests\ReturnTypeProvider;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class GetObjectVarsTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        yield 'returnsPublicProperties' => [
            'code' => '<?php
                final class C {
                    /** @var string */
                    public $prop = "val";
                }
                $ret = get_object_vars(new C);
            ',
            'assertions' => ['$ret' => 'array{prop: string}'],
        ];

        yield 'returnsSealedArrayForFinalClass' => [
            'code' => '<?php
                final class C {
                    /** @var string */
                    public $prop = "val";
                }
                $ret = get_object_vars(new C);
            ',
            'assertions' => ['$ret' => 'array{prop: string}'],
        ];

        yield 'omitsPrivateAndProtectedPropertiesWhenCalledOutsideOfClassScope' => [
            'code' => '<?php
                final class C {
                    /** @var string */
                    private $priv = "val";

                    /** @var string */
                    protected $prot = "val";
                }
                $ret = get_object_vars(new C);
            ',
            'assertions' => ['$ret' => 'array<never, never>'],
        ];

        yield 'includesPrivateAndProtectedPropertiesWhenCalledInsideClassScope' => [
            'code' => '<?php
                final class C {
                    /** @var string */
                    private $priv = "val";

                    /** @var string */
                    protected $prot = "val";

                    /** @return array{priv: string, prot: string} */
                    public function method(): array {
                        return get_object_vars($this);
                    }
                }
            ',
            'assertions' => [],
        ];

        yield 'includesProtectedAndOmitsPrivateFromParentWhenCalledInDescendant' => [
            'code' => '<?php
                class C {
                    /** @var string */
                    private $priv = "val";

                    /** @var string */
                    protected $prot = "val";

                    /** @var string */
                    public $pub = "val";
                }

                final class D extends C {
                    /** @return array{prot: string, pub: string} */
                    public function method(): array {
                        return get_object_vars($this);
                    }
                }
            ',
            'assertions' => [],
        ];

        yield 'propertiesOfObjectWithKeys' => [
            'code' => '<?php
                /**
                 * @param object{a:int, b:string, c:bool} $p
                 * @return array{a:int, b:string, c:bool}
                 */
                function f(object $p): array {
                    return get_object_vars($p);
                }
            ',
            'assertions' => [],
        ];

        yield 'propertiesOfCastScalar' => [
            'code' => '<?php $ret = get_object_vars((object)true);',
            'assertions' => ['$ret' => 'array{scalar: true}'],
        ];

        yield 'propertiesOfPOPO' => [
            'code' => '<?php $ret = get_object_vars((object)["a" => 1]);',
            'assertions' => ['$ret' => 'array{a: int}'],
        ];

        yield 'templatedProperties' => [
            'code' => '<?php
                /** @template T */
                final class a {
                    /** @param T $t */
                    public function __construct(public mixed $t) {}
                }

                $a = get_object_vars(new a("test"));',
            'assertions' => [
                '$a===' => "array{t: 'test'}",
            ],
        ];

        yield 'SKIPPED-dynamicProperties' => [
            'code' => '<?php
                class a {
                    public function __construct(public string $t) {}
                }

                $a = new a("test");
                $a->b = "test";
                $test = get_object_vars($a);',
            'assertions' => [
                '$test===' => "array{t: 'test'}",
            ],
        ];

        yield 'SKIPPED-dynamicProperties82' => [
            'code' => '<?php
                #[AllowDynamicProperties]
                class a {
                    public function __construct(public string $t) {}
                }

                $a = new a("test");
                $a->b = "test";
                $test = get_object_vars($a);',
            'assertions' => [
                '$test===' => "array{t: 'test'}",
            ],
            'php_version' => '8.2',
        ];

        yield 'SKIPPED-noDynamicProperties82' => [
            'code' => '<?php
                class a {
                    public function __construct(public string $t) {}
                }

                $a = new a("test");
                $test = get_object_vars($a);',
            'assertions' => [
                '$test===' => "array{t: 'test'}",
            ],
            'php_version' => '8.2',
        ];

        yield 'UnitEnum generic' => [
            'code' => <<<'PHP'
                <?php
                enum A { case One; case Two; }
                function getUnitEnum(): UnitEnum { return A::One; }
                $b = get_object_vars(getUnitEnum());
                PHP,
            'assertions' => [
                '$b===' => 'array{name: non-empty-string}',
            ],
            'ignored_issues' => [],
            'php_version' => '8.1',
        ];
        yield 'UnitEnum specific' => [
            'code' => <<<'PHP'
                <?php
                enum A { case One; case Two; }
                function getUnitEnum(): A { return A::One; }
                $b = get_object_vars(getUnitEnum());
                PHP,
            'assertions' => [
                '$b===' => "array{name: 'One'|'Two'}",
            ],
            'ignored_issues' => [],
            'php_version' => '8.1',
        ];
        yield 'UnitEnum literal' => [
            'code' => <<<'PHP'
                <?php
                enum A { case One; case Two; }
                $b = get_object_vars(A::One);
                PHP,
            'assertions' => [
                '$b===' => "array{name: 'One'}",
            ],
            'ignored_issues' => [],
            'php_version' => '8.1',
        ];
        yield 'BackedEnum generic' => [
            'code' => <<<'PHP'
                <?php
                enum A: int { case One = 1; case Two = 2; }
                function getBackedEnum(): BackedEnum { return A::One; }
                $b = get_object_vars(getBackedEnum());
                PHP,
            'assertions' => [
                '$b===' => 'array{name: non-empty-string, value: int|string}',
            ],
            'ignored_issues' => [],
            'php_version' => '8.1',
        ];
        yield 'Int BackedEnum specific' => [
            'code' => <<<'PHP'
                <?php
                enum A: int { case One = 1; case Two = 2; }
                function getBackedEnum(): A { return A::One; }
                $b = get_object_vars(getBackedEnum());
                PHP,
            'assertions' => [
                '$b===' => "array{name: 'One'|'Two', value: 1|2}",
            ],
            'ignored_issues' => [],
            'php_version' => '8.1',
        ];
        yield 'String BackedEnum specific' => [
            'code' => <<<'PHP'
                <?php
                enum A: string { case One = "one"; case Two = "two"; }
                function getBackedEnum(): A { return A::One; }
                $b = get_object_vars(getBackedEnum());
                PHP,
            'assertions' => [
                '$b===' => "array{name: 'One'|'Two', value: 'one'|'two'}",
            ],
            'ignored_issues' => [],
            'php_version' => '8.1',
        ];
        yield 'Int BackedEnum literal' => [
            'code' => <<<'PHP'
                <?php
                enum A: int { case One = 1; case Two = 2; }
                $b = get_object_vars(A::One);
                PHP,
            'assertions' => [
                '$b===' => "array{name: 'One', value: 1}",
            ],
            'ignored_issues' => [],
            'php_version' => '8.1',
        ];
        yield 'String BackedEnum literal' => [
            'code' => <<<'PHP'
                <?php
                enum A: string { case One = "one"; case Two = "two"; }
                $b = get_object_vars(A::One);
                PHP,
            'assertions' => [
                '$b===' => "array{name: 'One', value: 'one'}",
            ],
            'ignored_issues' => [],
            'php_version' => '8.1',
        ];
        yield 'Interface extending UnitEnum' => [
            'code' => <<<'PHP'
                <?php
                interface A extends UnitEnum {}
                enum B implements A { case One; }
                function getA(): A { return B::One; }
                $b = get_object_vars(getA());
                PHP,
            'assertions' => [
                '$b===' => 'array{name: non-empty-string}',
            ],
            'ignored_issues' => [],
            'php_version' => '8.1',
        ];
        yield 'Interface extending BackedEnum' => [
            'code' => <<<'PHP'
                <?php
                interface A extends BackedEnum {}
                enum B: int implements A { case One = 1; }
                function getA(): A { return B::One; }
                $b = get_object_vars(getA());
                PHP,
            'assertions' => [
                '$b===' => 'array{name: non-empty-string, value: int|string}',
            ],
            'ignored_issues' => [],
            'php_version' => '8.1',
        ];
    }
}
