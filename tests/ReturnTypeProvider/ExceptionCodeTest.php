<?php

namespace Psalm\Tests\ReturnTypeProvider;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ExceptionCodeTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{code:string,assertions?:array<string,string>,ignored_issues?:list<string>,php_version?:string}>
     */
    public function providerValidCodeParse(): iterable
    {
        yield 'RuntimeException' => [
            'code' => '<?php
                function f(\RuntimeException $e): int {
                    return $e->getCode();
                }
            ',
            'assertions' => [],
        ];
        yield 'LogicException' => [
            'code' => '<?php
                function f(\LogicException $e): int {
                    return $e->getCode();
                }
            ',
            'assertions' => [],
        ];
        yield 'PDOException' => [
            'code' => '<?php
                function f(\PDOException $e): string {
                    return $e->getCode();
                }
            ',
            'assertions' => [],
        ];
        yield 'Exception' => [
            'code' => '<?php
                /** @var \Throwable $e */
                $code = $e->getCode();
            ',
            'assertions' => ['$code' => 'int|string'],
        ];
        yield 'Throwable' => [
            'code' => '<?php
                /** @var \Exception $e */
                $code = $e->getCode();
            ',
            'assertions' => ['$code' => 'int|string'],
        ];
    }
}
