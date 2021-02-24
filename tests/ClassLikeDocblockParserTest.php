<?php
namespace Psalm\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Psalm\Aliases;
use Psalm\DocComment;
use Psalm\Internal\RuntimeCaches;
use Psalm\Internal\Scanner\ParsedDocblock;
use Psalm\Internal\PhpVisitor\Reflector\ClassLikeDocblockParser;
use PhpParser\Node\Stmt\Class_;

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
}
