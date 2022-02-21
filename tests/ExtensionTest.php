<?php

namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ExtensionTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{code:string,error_message:string,ignored_issues?:list<string>,php_version?:string,required_extensions?:list<value-of<Config::SUPPORTED_EXTENSIONS>>}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        // Note: This test should only use extensions that are required by Psalm to ensure that the extensions are
        // installed for the CI run. The point of this test is to make sure reflection doesn't load supported extensions
        // that aren't required by the project, the test doesn't work if the extension being tested isn't installed when
        // the test is run.
        yield 'undefinedClass' => [
            'code' => '<?php
                new DOMException();
            ',
            'error_message' => 'UndefinedClass',
        ];
        yield 'undefinedInterface' => [
            'code' => '<?php
                class Foo implements DOMParentNode {}
            ',
            'error_message' => 'UndefinedClass',
            'ignored_issues' => ['UnimplementedInterfaceMethod'],
            'php_version' => '8.0',
        ];
        yield 'undefinedFunction' => [
            'code' => '<?php
                simplexml_load_file("somefile");
            ',
            'error_message' => 'UndefinedFunction',
        ];
        yield 'undefinedConstant' => [
            'code' => '<?php
                echo XML_ELEMENT_NODE;
            ',
            'error_message' => 'UndefinedConstant',
        ];

        // Additional tests that aren't double checked by providerValidCodeParse
        yield 'SKIPPED-defineFunctionWithSameNameAsExtensionFunction' => [
            'code' => '<?php
                function simplexml_load_file(): void {}
            ',
            'error_message' => 'DuplicateFunction',
            'ignored_issues' => [],
            'php_version' => '7.3', // Not needed, only here because required_extensions has to be set
            'required_extensions' => ['simplexml'],
        ];
        yield 'SKIPPED-defineConstantWithSameNameAsExtensionConstant' => [ // https://github.com/vimeo/psalm/issues/7646
            'code' => '<?php
                const XML_ELEMENT_NODE = 1;
            ',
            'error_message' => 'DuplicateFunction',
            'ignored_issues' => [],
            'php_version' => '7.3', // Not needed, only here because required_extensions has to be set
            'required_extensions' => ['dom'],
        ];
    }

    /**
     * @return iterable<string,array{code:string,assertions?:array<string,string>,ignored_issues?:list<string>,php_version?:string,required_extensions?:list<value-of<Config::SUPPORTED_EXTENSIONS>>}>
     */
    public function providerValidCodeParse(): iterable
    {
        // This tests the same code as providerInvalidCodeParse, except with the extensions enabled. This ensures that
        // if a tested class, function, constant, etc were to be removed from an extension in the future, this test will
        // fail. Otherwise, the invalid code test will continue to succeed but won't actually be testing what it's
        // supposed to.
        foreach ($this->providerInvalidCodeParse() as $name => $test) {
            if (isset($test['required_extensions'])) {
                continue;
            }
            yield $name => [
                'code' => $test['code'],
                'assertions' => [],
                'ignored_issues' => $test['ignored_issues'] ?? [],
                'php_version' => $test['php_version'] ?? '7.3',
                'required_extensions' => ['dom', 'simplexml'],
            ];
        }

        yield 'defineFunctionWithSameNameAsExtensionFunction' => [
            'code' => '<?php
                function simplexml_load_file(): void {}
            ',
        ];
        yield 'defineConstantWithSameNameAsExtensionConstant' => [
            'code' => '<?php
                const XML_ELEMENT_NODE = 1;
            ',
        ];
    }
}
