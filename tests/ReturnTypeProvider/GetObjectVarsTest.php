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
            '<?php
                class C {
                    /** @var string */
                    public $prop = "val";
                }
                $ret = get_object_vars(new C);
            ',
            ['$ret' => 'array{prop: string}'],
        ];

        yield 'omitsPrivateAndProtectedPropertiesWhenCalledOutsideOfClassScope' => [
            '<?php
                class C {
                    /** @var string */
                    private $priv = "val";

                    /** @var string */
                    protected $prot = "val";
                }
                $ret = get_object_vars(new C);
            ',
            ['$ret' => 'array<never, never>'],
        ];

        yield 'includesPrivateAndProtectedPropertiesWhenCalledInsideClassScope' => [
            '<?php
                class C {
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
            [],
        ];

        yield 'includesProtectedAndOmitsPrivateFromParentWhenCalledInDescendant' => [
            '<?php
                class C {
                    /** @var string */
                    private $priv = "val";

                    /** @var string */
                    protected $prot = "val";

                    /** @var string */
                    public $pub = "val";
                }

                class D extends C {
                    /** @return array{prot: string} */
                    public function method(): array {
                        return get_object_vars($this);
                    }
                }
            ',
            [],
        ];

        yield 'propertiesOfObjectWithKeys' => [
            '<?php
                /**
                 * @param object{a:int, b:string, c:bool} $p
                 * @return array{a:int, b:string, c:bool}
                 */
                function f(object $p): array {
                    return get_object_vars($p);
                }
            ',
            [],
        ];

        yield 'propertiesOfPOPO' => [
            // todo: fix object cast so that it results in `object{a:1}` instead
            '<?php $ret = get_object_vars((object)["a" => 1]);',
            ['$ret' => 'array<string, mixed>'],
        ];
    }
}
