<?php

namespace Psalm\Tests;

use PhpParser\Comment\Doc;
use PhpParser\Node\Stmt\Class_;
use Psalm\Aliases;
use Psalm\Internal\PhpVisitor\Reflector\ClassLikeDocblockParser;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ClassLikeDocblockParserTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    public function testDocblockDescription(): void
    {
        $doc = '/**
 * Some Description
 *
 */
';
        $node = new Class_(null);
        $php_parser_doc = new Doc($doc);
        $class_docblock = ClassLikeDocblockParser::parse($node, $php_parser_doc, new Aliases());

        $this->assertSame('Some Description', $class_docblock->description);
    }

    public function testPreferPsalmPrefixedAnnotationsOverPhpstanOnes(): void
    {
        $doc = '/**
 * @psalm-template-covariant T of string
 * @phpstan-template T of int
 */
';
        $node = new Class_(null);
        $php_parser_doc = new Doc($doc);
        $class_docblock = ClassLikeDocblockParser::parse($node, $php_parser_doc, new Aliases());
        $this->assertSame([['T', 'of', 'string', true, 33]], $class_docblock->templates);
    }

    /**
     * @return iterable<string,array{code:string,assertions?:array<string,string>,ignored_issues?:list<string>,php_version?:string}>
     */
    public function providerValidCodeParse(): iterable
    {
        yield 'dontCrashOnInvalidClassConstDocblock' => [
            'code' => '<?php
                class Foo
                {
                    /**
                     * @psalm-does-not-exist
                     */
                    public const CONST = 1;
                }
            ',
            'assertions' => [],
            'ignored_issues' => ['InvalidDocblock'],
        ];
    }
}
