<?php
namespace Psalm\Tests;

use PhpParser\Node\Stmt\Class_;
use Psalm\Aliases;
use Psalm\Internal\PhpVisitor\Reflector\ClassLikeDocblockParser;

class ClassLikeDocblockParserTest extends \Psalm\Tests\TestCase
{
    public function testDocblockDescription(): void
    {
        $doc = '/**
 * Some Description
 *
 */
';
        $node = new Class_(null);
        $php_parser_doc = new \PhpParser\Comment\Doc($doc);
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
        $php_parser_doc = new \PhpParser\Comment\Doc($doc);
        $class_docblock = ClassLikeDocblockParser::parse($node, $php_parser_doc, new Aliases());
        $this->assertSame([['T', 'of', 'string', true, 33]], $class_docblock->templates);
    }
}
