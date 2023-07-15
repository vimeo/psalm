<?php

namespace Psalm\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use PhpParser\Comment\Doc;
use Psalm\Aliases;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\RuntimeCaches;
use Psalm\Internal\Scanner\FileScanner;

class CommentAnalyzerTest extends BaseTestCase
{
    public function setUp(): void
    {
        RuntimeCaches::clearAll();
    }

    public function testDocblockVarDescription(): void
    {
        $doc = '/**
 * @var string Some Description
 */
';
        $php_parser_doc = new Doc($doc);
        $comment_docblock = CommentAnalyzer::getTypeFromComment($php_parser_doc, new FileScanner('somefile.php', 'somefile.php', false), new Aliases);
        $this->assertSame('Some Description', $comment_docblock[0]->description);
    }

    public function testDocblockVarDescriptionWithVarId(): void
    {
        $doc = '/**
 * @var string $foo Some Description
 */
';
        $php_parser_doc = new Doc($doc);
        $comment_docblock = CommentAnalyzer::getTypeFromComment($php_parser_doc, new FileScanner('somefile.php', 'somefile.php', false), new Aliases);
        $this->assertSame('Some Description', $comment_docblock[0]->description);
    }

    public function testDocblockVarDescriptionMultiline(): void
    {
        $doc = '/**
 * @var string $foo Some Description
 *                  with a long description.
 */
';
        $php_parser_doc = new Doc($doc);
        $comment_docblock = CommentAnalyzer::getTypeFromComment($php_parser_doc, new FileScanner('somefile.php', 'somefile.php', false), new Aliases);
        $this->assertSame('Some Description with a long description.', $comment_docblock[0]->description);
    }

    public function testDocblockDescription(): void
    {
        $doc = '/**
 * Some Description
 *
 * @var string
 */
';
        $php_parser_doc = new Doc($doc);
        $comment_docblock = CommentAnalyzer::getTypeFromComment($php_parser_doc, new FileScanner('somefile.php', 'somefile.php', false), new Aliases);
        $this->assertSame('Some Description', $comment_docblock[0]->description);
    }

    public function testDocblockDescriptionWithVarDescription(): void
    {
        $doc = '/**
 * Some Description
 *
 * @var string Use a string
 */
';
        $php_parser_doc = new Doc($doc);
        $comment_docblock = CommentAnalyzer::getTypeFromComment($php_parser_doc, new FileScanner('somefile.php', 'somefile.php', false), new Aliases);
        $this->assertSame('Use a string', $comment_docblock[0]->description);
    }

    /**
     * @dataProvider providerSanitizeDocblockType
     */
    public function testSanitizeDocblockType(string $doc_block_type, string $expected): void
    {
        $this->assertSame($expected, CommentAnalyzer::sanitizeDocblockType($doc_block_type));
    }

    public function providerSanitizeDocblockType(): iterable
    {
        return [
            'arrayShapeComments' => [
                'doc_block_type' => <<<EOT
                    array{ // Comment
                        // Comment
                        key1: int, // Comment
                        // Comment
                        key2: {, // Comment
                            key2_1: string, // Comment
                        } // Comment
                    }
                    EOT,
                'expected' => 'array{         key1: int,         key2: {,         key2_1: string,     } }',
            ],
        ];
    }

    /**
     * @dataProvider providerSplitDocLine
     * @param string[] $expected
     */
    public function testSplitDocLine(string $doc_line, array $expected): void
    {
        $this->assertSame($expected, CommentAnalyzer::splitDocLine($doc_line));
    }

    /**
     * @return iterable<array-key, array{doc_line: string, expected: string[]}>
     */
    public function providerSplitDocLine(): iterable
    {
        return [
            'typeWithVar' => [
                'doc_line' =>
                    'TArray $array',
                'expected' => [
                    'TArray',
                    '$array',
                ],
            ],
            'arrayShape' => [
                'doc_line' =>
                    'array{
                     *     a: int,
                     *     b: string,
                     * }',
                'expected' => [
                    'array{
                     *     a: int,
                     *     b: string,
                     * }',
                ],
            ],
            'arrayShapeWithSpace' => [
                'doc_line' =>
                    'array {
                     *     a: int,
                     *     b: string,
                     * }',
                'expected' => [
                    'array {
                     *     a: int,
                     *     b: string,
                     * }',
                ],
            ],
            'func_num_args' => [
                'doc_line' =>
                    '(
                     *     func_num_args() is 1
                     *     ? array{dirname: string, basename: string, extension?: string, filename: string}
                     *     : string
                     * )',
                'expected' => [
                    '(
                     *     func_num_args() is 1
                     *     ? array{dirname: string, basename: string, extension?: string, filename: string}
                     *     : string
                     * )',
                ],
            ],
        ];
    }
}
