<?php

namespace Psalm\Tests\ReturnTypeProvider;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ExceptionCodeTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        yield 'RuntimeException' => [
            '<?php
                function f(\RuntimeException $e): int {
                    return $e->getCode();
                }
            ',
            [],
        ];
        yield 'LogicException' => [
            '<?php
                function f(\LogicException $e): int {
                    return $e->getCode();
                }
            ',
            [],
        ];
        yield 'PDOException' => [
            '<?php
                function f(\PDOException $e): string {
                    return $e->getCode();
                }
            ',
            [],
        ];
        yield 'Exception' => [
            '<?php
                /** @var \Throwable $e */
                $code = $e->getCode();
            ',
            ['$code' => 'int|string'],
        ];
        yield 'Throwable' => [
            '<?php
                /** @var \Exception $e */
                $code = $e->getCode();
            ',
            ['$code' => 'int|string'],
        ];
    }
}
