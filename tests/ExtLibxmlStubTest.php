<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ExtLibxmlStubTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'dom_import_simplexml, php >=8.0' => [
                'code' => <<<'PHP'
                    <?php
                    $x = dom_import_simplexml(new SimpleXMLElement(''));
                    PHP,
                'assertions' => ['$x===' => 'DOMElement'],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'dom_import_simplexml, php <8.0' => [
                'code' => <<<'PHP'
                    <?php
                    $x = dom_import_simplexml(new SimpleXMLElement(''));
                    PHP,
                'assertions' => ['$x===' => 'DOMElement|null'],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'dom_import_simplexml, $node arg' => [
                'code' => <<<'PHP'
                    <?php
                    $x = dom_import_simplexml(new \stdClass());
                    PHP,
                'error_message' => 'InvalidArgument',
            ],
        ];
    }
}
