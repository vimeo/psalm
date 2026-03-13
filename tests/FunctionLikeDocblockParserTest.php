<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use PHPUnit\Framework\TestCase as BaseTestCase;
use PhpParser\Comment\Doc;
use PhpParser\Node\Scalar\String_;
use Psalm\CodeLocation;
use Psalm\Exception\IncorrectDocblockException;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\PhpVisitor\Reflector\FunctionLikeDocblockParser;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;

final class FunctionLikeDocblockParserTest extends BaseTestCase
{
    public string $test_cased_function_id = 'hello_world';

    public CodeLocation $test_code_location;

    #[Override]
    public function setUp(): void
    {
        RuntimeCaches::clearAll();

        $file_provider = new FakeFileProvider();

        $providers = new Providers(
            $file_provider,
            new FakeParserCacheProvider(),
        );

        $test_config = new TestConfig();

        $project_analyzer = new ProjectAnalyzer(
            $test_config,
            $providers,
        );

        $file_analyzer = new FileAnalyzer($project_analyzer, 'none/none.php', 'none.php');

        $stmt = new String_('randomString');
        $this->test_code_location = new CodeLocation($file_analyzer, $stmt);
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
        $function_docblock = FunctionLikeDocblockParser::parse(
            ProjectAnalyzer::getInstance()->getCodebase(),
            $php_parser_doc,
            $this->test_code_location,
            $this->test_cased_function_id,
        );

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
        $function_docblock = FunctionLikeDocblockParser::parse(
            ProjectAnalyzer::getInstance()->getCodebase(),
            $php_parser_doc,
            $this->test_code_location,
            $this->test_cased_function_id,
        );

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
        FunctionLikeDocblockParser::parse(
            ProjectAnalyzer::getInstance()->getCodebase(),
            $php_parser_doc,
            $this->test_code_location,
            $this->test_cased_function_id,
        );
    }

    public function testPreferPsalmPrefixedAnnotationsOverPhpstanOnes(): void
    {
        $doc = '/**
 * @psalm-template T of string
 * @phpstan-template T of int
 */
';
        $php_parser_doc = new Doc($doc);
        $function_docblock = FunctionLikeDocblockParser::parse(
            ProjectAnalyzer::getInstance()->getCodebase(),
            $php_parser_doc,
            $this->test_code_location,
            $this->test_cased_function_id,
        );
        $this->assertSame([['T', 'of', 'string', false]], $function_docblock->templates);
    }

    public function testReturnsUnexpectedTags(): void
    {
        $doc = '/**
 * @psalm-import-type abcd
 * @var int $p
 * @psalm-consistent-constructor
 */
';
        $php_parser_doc = new Doc($doc, 0);
        $function_docblock = FunctionLikeDocblockParser::parse(
            ProjectAnalyzer::getInstance()->getCodebase(),
            $php_parser_doc,
            $this->test_code_location,
            $this->test_cased_function_id,
        );
        $this->assertEquals(
            [
                'psalm-import-type' => ['lines' => [1]],
                'var' => ['lines' => [2], 'suggested_replacement' => 'param'],
                'psalm-consistent-constructor' => [
                    'lines' => [3],
                    'suggested_replacement' => 'psalm-consistent-constructor on a class level',
                ],
            ],
            $function_docblock->unexpected_tags,
        );
    }
}
