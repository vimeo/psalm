<?php

declare(strict_types=1);

namespace Psalm\Tests\ReturnTypeProvider;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class ExceptionCodeTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public function providerValidCodeParse(): iterable
    {
        yield 'RuntimeException' => [
            'code' => '<?php
                /** @var \RuntimeException $e */
                $code = $e->getCode();
            ',
            'assertions' => ['$code' => 'int|string'],
        ];
        yield 'CustomRuntimeException' => [
            'code' => '<?php
                class CustomRuntimeException extends \RuntimeException {}

                /** @var CustomRuntimeException $e */
                $code = $e->getCode();
            ',
            'assertions' => ['$code' => 'int'],
        ];
        yield 'LogicException' => [
            'code' => '<?php
                /** @var \LogicException $e */
                $code = $e->getCode();
            ',
            'assertions' => ['$code' => 'int'],
        ];
        yield 'PDOException' => [
            'code' => '<?php
                /** @var \PDOException $e */
                $code = $e->getCode();
            ',
            'assertions' => ['$code' => 'int|string'],
        ];
        yield 'CustomThrowable' => [
            'code' => '<?php
                interface CustomThrowable extends \Throwable {}

                /** @var CustomThrowable $e */
                $code = $e->getCode();
            ',
            'assertions' => ['$code' => 'int'],
        ];
        yield 'Throwable' => [
            'code' => '<?php
                /** @var \Throwable $e */
                $code = $e->getCode();
            ',
            'assertions' => ['$code' => 'int|string'],
        ];
        yield 'Exception' => [
            'code' => '<?php
                /** @var \Exception $e */
                $code = $e->getCode();
            ',
            'assertions' => ['$code' => 'int|string'],
        ];
    }
}
