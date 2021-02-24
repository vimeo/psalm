<?php
namespace Psalm\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Psalm\DocComment;
use Psalm\Internal\RuntimeCaches;
use Psalm\Internal\Scanner\ParsedDocblock;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Scanner\FileScanner;
use Psalm\Aliases;

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
        $php_parser_doc = new \PhpParser\Comment\Doc($doc);
        $comment_docblock = CommentAnalyzer::getTypeFromComment($php_parser_doc, new FileScanner('somefile.php', 'somefile.php', false), new Aliases);
        $this->assertSame('Some Description', $comment_docblock[0]->description);
    }

    public function testDocblockVarDescriptionWithVarId(): void
    {
        $doc = '/**
 * @var string $foo Some Description
 */
';
        $php_parser_doc = new \PhpParser\Comment\Doc($doc);
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
        $php_parser_doc = new \PhpParser\Comment\Doc($doc);
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
        $php_parser_doc = new \PhpParser\Comment\Doc($doc);
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
        $php_parser_doc = new \PhpParser\Comment\Doc($doc);
        $comment_docblock = CommentAnalyzer::getTypeFromComment($php_parser_doc, new FileScanner('somefile.php', 'somefile.php', false), new Aliases);
        $this->assertSame('Use a string', $comment_docblock[0]->description);
    }
}
