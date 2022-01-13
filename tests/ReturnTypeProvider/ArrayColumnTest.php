<?php

namespace Psalm\Tests\ReturnTypeProvider;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ArrayColumnTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        yield 'arrayColumnObjectWithProperties' => [
            'code' => '<?php
                /**
                 * @param object{id: int} $o
                 * @return non-empty-list<int>
                 */
                function f(object $o): array {
                    return array_column([$o], "id");
                }
            ',
        ];

        yield 'arrayColumnWithPrivatePropertiesExternal' => [
            'code' => '<?php
                class C {
                    /** @var int */
                    private $id = 42;
                }
                $r = array_column([new C], "id");
            ',
            // for inaccessible properties we cannot figure out neither type nor emptiness
            // in practice, array_column() omits inaccessible elements
            'assertions' => ['$r' => 'list<mixed>'],
        ];

        yield 'arrayColumnWithPrivatePropertiesInternal' => [
            'code' => '<?php
                class C {
                    /** @var int */
                    private $id = 42;

                    /** @return non-empty-list<int> */
                    public function f(): array {
                        return array_column([new self], "id");
                    }
                }
            ',
        ];

        yield 'arrayColumnWithShapes' => [
            'code' => '<?php
                /**
                 * @param array{id:int} $shape
                 * @return non-empty-list<int>
                 */
                function f(array $shape): array {
                    return array_column([$shape], "id");
                }
            ',
        ];
    }
}
