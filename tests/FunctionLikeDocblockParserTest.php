<?php

namespace Psalm\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use PhpParser\Comment\Doc;
use Psalm\Exception\IncorrectDocblockException;
use Psalm\Internal\PhpVisitor\Reflector\FunctionLikeDocblockParser;
use Psalm\Internal\RuntimeCaches;

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
        $php_parser_doc = new Doc($doc);
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
        $php_parser_doc = new Doc($doc);
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
        $php_parser_doc = new Doc($doc);
        $this->expectException(IncorrectDocblockException::class);
        $this->expectExceptionMessage('Misplaced variable');
        FunctionLikeDocblockParser::parse($php_parser_doc);
    }

    public function testPreferPsalmPrefixedAnnotationsOverPhpstanOnes(): void
    {
        $doc = '/**
 * @psalm-template T of string
 * @phpstan-template T of int
 */
';
        $php_parser_doc = new Doc($doc);
        $function_docblock = FunctionLikeDocblockParser::parse($php_parser_doc);
        $this->assertSame([['T', 'of', 'string', false]], $function_docblock->templates);
    }

    public function testReturnsUnexpectedTags(): void
    {
        $doc = '/**
 * @psalm-import-type abcd
 * @var int $p
 */
';
        $php_parser_doc = new Doc($doc, 0);
        $function_docblock = FunctionLikeDocblockParser::parse($php_parser_doc);
        $this->assertEquals(
            [
                'psalm-import-type' => ['lines' => [1]],
                'var' => ['lines' => [2], 'suggested_replacement' => 'param'],
            ],
            $function_docblock->unexpected_tags
        );
    }
}
