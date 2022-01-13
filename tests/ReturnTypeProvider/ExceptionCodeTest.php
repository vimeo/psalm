<?php

namespace Psalm\Tests\ReturnTypeProvider;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ExceptionCodeTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        yield 'RuntimeException' => [
            '<?php
                function f(\RuntimeException $e) {
                    $code = $e->getCode();
                    return $code;
                }
            ',
            ['$code' => 'int'],
        ];
        yield 'LogicException' => [
            '<?php
                function f(\LogicException $e) {
                    $code = $e->getCode();
                    return $code;
                }
            ',
            ['$code' => 'int'],
        ];
        yield 'PDOException' => [
            '<?php
                function f(\PDOException $e) {
                    $code = $e->getCode();
                    return $code;
                }
            ',
            ['$code' => 'string'],
        ];
        yield 'Exception' => [
            '<?php
                function f(\Exception $e) {
                    $code = $e->getCode();
                    return $code;
                }
            ',
            ['$code' => 'int|string'],
        ];
        yield 'Throwable' => [
            '<?php
                function f(\Throwable $e) {
                    $code = $e->getCode();
                    return $code;
                }
            ',
            ['$code' => 'int|string'],
        ];
    }
}
