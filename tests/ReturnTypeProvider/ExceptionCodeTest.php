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
                /** @var \RuntimeException $e */
                $code = $e->getCode();
            ',
            ['$code' => 'int|string'],
        ];
        yield 'CustomRuntimeException' => [
            '<?php
                class CustomRuntimeException extends \RuntimeException {}

                /** @var CustomRuntimeException $e */
                $code = $e->getCode();
            ',
            ['$code' => 'int'],
        ];
        yield 'LogicException' => [
            '<?php
                /** @var \LogicException $e */
                $code = $e->getCode();
            ',
            ['$code' => 'int'],
        ];
        yield 'PDOException' => [
            '<?php
                /** @var \PDOException $e */
                $code = $e->getCode();
            ',
            ['$code' => 'int|string'],
        ];
        yield 'CustomThrowable' => [
            '<?php
                interface CustomThrowable extends \Throwable {}

                /** @var CustomThrowable $e */
                $code = $e->getCode();
            ',
            ['$code' => 'int'],
        ];
        yield 'Throwable' => [
            '<?php
                /** @var \Throwable $e */
                $code = $e->getCode();
            ',
            ['$code' => 'int|string'],
        ];
        yield 'Exception' => [
            '<?php
                /** @var \Exception $e */
                $code = $e->getCode();
            ',
            ['$code' => 'int|string'],
        ];
    }
}
