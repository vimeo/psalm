<?php
namespace Psalm\Tests;

use PhpParser\Node\Stmt\Class_;
use Psalm\Aliases;
use Psalm\Internal\PhpVisitor\Reflector\ClassLikeDocblockParser;
use Psalm\Internal\PhpVisitor\Reflector\ClassLikeNodeScanner;
use Psalm\Internal\Type\ParseTreeCreator;

class ClassLikeNodeScannerTest extends \Psalm\Tests\TestCase
{
    public function testComplexPsalmType(): void
    {
        $doc = '/**
 * @psalm-type TypedArrayHandler callable(array<string>): void
 */
';
        $php_parser_doc = new \PhpParser\Comment\Doc($doc);
        $type_aliases = ClassLikeNodeScanner::getTypeAliasesFromComment($php_parser_doc, new Aliases(), [], null);
        $this->assertArrayHasKey('TypedArrayHandler', $type_aliases);
        $this->assertSame(
            [
                ['callable', 0],
                ['(', 8],
                ['array', 9],
                ['<', 14],
                ['string', 15],
                ['>', 21],
                [')', 22],
                [':', 23],
                ['void', 25],
            ],
            $type_aliases['TypedArrayHandler']
        );
    }
}
