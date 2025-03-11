<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use PHPUnit\Framework\TestCase as BaseTestCase;
use PhpParser\Comment\Doc;
use Psalm\Aliases;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\Internal\Scanner\FileScanner;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;

final class CommentAnalyzerTest extends BaseTestCase
{
    private Codebase $codebase;

    #[Override]
    public function setUp(): void
    {
        RuntimeCaches::clearAll();

        $file_provider = new FakeFileProvider();
        $this->codebase = (new ProjectAnalyzer(
            new TestConfig(),
            new Providers(
                $file_provider,
                new FakeParserCacheProvider(),
            ),
        ))->getCodebase();
    }

    public function testDocblockVarDescription(): void
    {
        $doc = '/**
 * @var string Some Description
 */
';
        $php_parser_doc = new Doc($doc);
        $comment_docblock = CommentAnalyzer::getTypeFromComment($this->codebase, $php_parser_doc, new FileScanner('somefile.php', 'somefile.php', false), new Aliases);
        $this->assertSame('Some Description', $comment_docblock[0]->description);
    }

    public function testDocblockVarDescriptionWithVarId(): void
    {
        $doc = '/**
 * @var string $foo Some Description
 */
';
        $php_parser_doc = new Doc($doc);
        $comment_docblock = CommentAnalyzer::getTypeFromComment($this->codebase, $php_parser_doc, new FileScanner('somefile.php', 'somefile.php', false), new Aliases);
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
        $comment_docblock = CommentAnalyzer::getTypeFromComment($this->codebase, $php_parser_doc, new FileScanner('somefile.php', 'somefile.php', false), new Aliases);
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
        $comment_docblock = CommentAnalyzer::getTypeFromComment($this->codebase, $php_parser_doc, new FileScanner('somefile.php', 'somefile.php', false), new Aliases);
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
        $comment_docblock = CommentAnalyzer::getTypeFromComment($this->codebase, $php_parser_doc, new FileScanner('somefile.php', 'somefile.php', false), new Aliases);
        $this->assertSame('Use a string', $comment_docblock[0]->description);
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
            'arrayShapeWithComments' => [
                'doc_line' =>
                    'array { // Comment
                     *     // Comment
                     *     a: int, // Comment
                     *     // Comment
                     *     b: string, // Comment
                     *     // Comment
                     * }',
                'expected' => [
                    "array {
                     *
                     *     a: int,
                     *
                     *     b: string,
                     *
                     * }",
                ],
            ],
            'arrayShapeWithSlashesInKeys' => [
                'doc_line' =>
                    <<<EOT
                    array {
                    *     // Single quote keys
                    *     array {
                    *         'single_quote_key//1': int, // Comment with ' in it
                    *         'single_quote_key//2': int, // Comment with ' in it
                    *         'single_quote_key\'//\'3': int, // Comment with ' in it
                    *         'single_quote_key"//"4': int, // Comment with ' in it
                    *     },
                    *     // Double quote keys
                    *     array {
                    *         "double_quote_key//1": int, // Comment with " in it
                    *         "double_quote_key//2": int, // Comment with " in it
                    *         "double_quote_key\"//\"3": int, // Comment with " in it
                    *         "double_quote_key'//'4": int, // Comment with " in it
                    *     }
                    * }
                    EOT,
                'expected' => [
                    <<<EOT
                    array {
                    *
                    *     array {
                    *         'single_quote_key//1': int,
                    *         'single_quote_key//2': int,
                    *         'single_quote_key\'//\'3': int,
                    *         'single_quote_key"//"4': int,
                    *     },
                    *
                    *     array {
                    *         "double_quote_key//1": int,
                    *         "double_quote_key//2": int,
                    *         "double_quote_key\"//\"3": int,
                    *         "double_quote_key'//'4": int,
                    *     }
                    * }
                    EOT,
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
