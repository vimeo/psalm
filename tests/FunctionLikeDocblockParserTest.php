<?php
namespace Psalm\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Psalm\Exception\IncorrectDocblockException;
use Psalm\Internal\RuntimeCaches;
use Psalm\Internal\PhpVisitor\Reflector\FunctionLikeDocblockParser;

class FunctionLikeDocblockParserTest extends BaseTestCase
{
    public function setUp(): void
    {
        RuntimeCaches::clearAll();
    }

    public function testDocblockDescription(): void
    {
        $doc = '/**
 * Some Description
 *
 * @param string $bli
 * @param int $bla
 *
 * @throws \Exception
 *
 * @return bool
 */
';
        $php_parser_doc = new \PhpParser\Comment\Doc($doc);
        $function_docblock = FunctionLikeDocblockParser::parse($php_parser_doc);

        $this->assertSame('Some Description', $function_docblock->description);
    }

    public function testDocblockParamDescription(): void
    {
        $doc = '/**
 * Some Description
 *
 * @param string $bli The BLI tag to iterate over.
 * @param int $bla    The blah tags
 *                    that has a very long multiline description.
 *
 * @throws \Exception
 *
 * @return bool
 */
';
        $php_parser_doc = new \PhpParser\Comment\Doc($doc);
        $function_docblock = FunctionLikeDocblockParser::parse($php_parser_doc);

        $this->assertTrue(isset($function_docblock->params[0]['description']));
        $this->assertSame('The BLI tag to iterate over.', $function_docblock->params[0]['description']);

        $this->assertTrue(isset($function_docblock->params[1]['description']));
        $this->assertSame('The blah tags that has a very long multiline description.', $function_docblock->params[1]['description']);
    }

    public function testMisplacedVariableOnNextLine(): void
    {
        $doc = '/**
 * @param
 *          $p
 */';
        $php_parser_doc = new \PhpParser\Comment\Doc($doc);
        $this->expectException(IncorrectDocblockException::class);
        $this->expectExceptionMessage('Misplaced variable');
        FunctionLikeDocblockParser::parse($php_parser_doc);
    }
}
