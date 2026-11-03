<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class ExtDomStubTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'querySelectorAll iterates Dom\Element' => [
                'code' => <<<'PHP'
                    <?php
                    $doc = Dom\HTMLDocument::createFromString('<a href="/">x</a>');
                    $links = [];
                    foreach ($doc->querySelectorAll('a') as $el) {
                        $links[] = $el;
                    }
                    PHP,
                'assertions' => ['$links===' => 'list<Dom\Element>'],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'querySelectorAll item is nullable Dom\Element' => [
                'code' => <<<'PHP'
                    <?php
                    $doc = Dom\HTMLDocument::createFromString('<a href="/">x</a>');
                    $first = $doc->querySelectorAll('a')->item(0);
                    PHP,
                'assertions' => ['$first===' => 'Dom\Element|null'],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'XPath query iterates Dom\Node' => [
                'code' => <<<'PHP'
                    <?php
                    $doc = Dom\HTMLDocument::createFromString('<p>x</p>');
                    $nodes = [];
                    foreach ((new Dom\XPath($doc))->query('//p') as $node) {
                        $nodes[] = $node;
                    }
                    PHP,
                'assertions' => ['$nodes===' => 'list<Dom\Node>'],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'childNodes iterates Dom\Node' => [
                'code' => <<<'PHP'
                    <?php
                    $doc = Dom\HTMLDocument::createFromString('<p>x</p>');
                    $children = [];
                    foreach ($doc->body?->childNodes ?? [] as $child) {
                        $children[] = $child;
                    }
                    PHP,
                'assertions' => ['$children===' => 'list<Dom\Node>'],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'getElementsByTagName iterates Dom\Element' => [
                'code' => <<<'PHP'
                    <?php
                    $doc = Dom\HTMLDocument::createFromString('<p>x</p>');
                    $paragraphs = [];
                    foreach ($doc->getElementsByTagName('p') as $p) {
                        $paragraphs[] = $p;
                    }
                    PHP,
                'assertions' => ['$paragraphs===' => 'list<Dom\Element>'],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'Dom\Node class constant resolves' => [
                'code' => <<<'PHP'
                    <?php
                    $flag = Dom\Node::DOCUMENT_POSITION_CONTAINS;
                    PHP,
                'assertions' => ['$flag===' => '8'],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'Dom classes are undefined before PHP 8.4' => [
                'code' => <<<'PHP'
                    <?php
                    $doc = Dom\HTMLDocument::createFromString('');
                    PHP,
                'error_message' => 'UndefinedClass',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
        ];
    }
}
