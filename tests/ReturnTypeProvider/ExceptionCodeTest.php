<?php

namespace Psalm\Tests\ReturnTypeProvider;

use Psalm\Config;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ExceptionCodeTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{code:string,assertions?:array<string,string>,ignored_issues?:list<string>,php_version?:string,required_extensions?:list<value-of<Config::SUPPORTED_EXTENSIONS>>}>
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
            'ignored_issues' => [],
            'php_version' => '7.3', // Not needed, only here because required_extensions has to be set
            'required_extensions' => ['pdo'],
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
