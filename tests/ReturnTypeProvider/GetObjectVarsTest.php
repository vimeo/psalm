<?php

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
                class C {
                    /** @var string */
                    public $prop = "val";
                }
                $ret = get_object_vars(new C);
            ',
            'assertions' => ['$ret' => 'unsealed-array{prop: string}'],
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
                class C {
                    /** @var string */
                    private $priv = "val";

                    /** @var string */
                    protected $prot = "val";

                    /** @return unsealed-array{priv: string, prot: string} */
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

                class D extends C {
                    /** @return unsealed-array{prot: string, pub: string} */
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
                 * @return unsealed-array{a:int, b:string, c:bool}
                 */
                function f(object $p): array {
                    return get_object_vars($p);
                }
            ',
            'assertions' => [],
        ];

        yield 'propertiesOfCastScalar' => [
            'code' => '<?php $ret = get_object_vars((object)true);',
            'assertions' => ['$ret' => 'unsealed-array{scalar: true}'],
        ];

        yield 'propertiesOfPOPO' => [
            'code' => '<?php $ret = get_object_vars((object)["a" => 1]);',
            'assertions' => ['$ret' => 'unsealed-array{a: int}'],
        ];
    }
}
