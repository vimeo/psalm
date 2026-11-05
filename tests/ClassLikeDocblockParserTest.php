<?php

declare(strict_types=1);

namespace Psalm\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PhpParser\Comment\Doc;
use PhpParser\Node\Stmt\Class_;
use Psalm\Aliases;
use Psalm\Internal\PhpVisitor\Reflector\ClassLikeDocblockParser;

use function array_values;

final class ClassLikeDocblockParserTest extends TestCase
{
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

    public function testTemplateDefaults(): void
    {
        $doc = '/**
 * @psalm-template TA of int = 5
 * @phpstan-template TA = 6
 * @phpstan-template TB = \'a=b\'
 * @template-covariant TC of callable(int=): void = callable(int=): void
 * @template TD of array{x?: int} = array{x?: int} description text
 * @template TE of string
 */
';
        $node = new Class_(null);
        $php_parser_doc = new Doc($doc, 10);
        $class_docblock = ClassLikeDocblockParser::parse($node, $php_parser_doc, new Aliases());

        $this->assertSame(
            [
                ['TA', 'of', 'int', false, 23],
                ['TB', null, null, false, 86],
                ['TC', 'of', 'callable(int=): void', true, 120],
                ['TD', 'of', 'array{x?: int}', false, 183],
                ['TE', 'of', 'string', false, 251],
            ],
            $class_docblock->templates,
        );
        $this->assertSame(
            [
                'TA' => ['type' => '5', 'line_number' => 11],
                'TB' => ['type' => '\'a=b\'', 'line_number' => 13],
                'TC' => ['type' => 'callable(int=): void', 'line_number' => 14],
                'TD' => ['type' => 'array{x?: int}', 'line_number' => 15],
            ],
            $class_docblock->template_defaults,
        );
    }

    public function testTemplateDefaultsWithEscapedQuotesAndEmptyDefault(): void
    {
        $doc = <<<'DOC'
            /**
             * @template TA of 'a\'=b'
             * @template TB of "c\"=d" = "c\"=d"
             * @template TC of 'e\\' = 'e\\'
             * @template TD of "f\\" description = text
             * @template TE of int =
             * @template TF of string
             */
            DOC;
        $node = new Class_(null);
        $class_docblock = ClassLikeDocblockParser::parse($node, new Doc($doc, 10), new Aliases());

        $this->assertSame(
            [
                ['TA', 'of', '\'a\\\'=b\'', false, 17],
                ['TB', 'of', '"c\\"=d"', false, 44],
                ['TC', 'of', '\'e\\\\\'', false, 81],
                ['TD', 'of', '"f\\\\" description = text', false, 114],
                ['TE', 'of', 'int', false, 158],
                ['TF', 'of', 'string', false, 183],
            ],
            $class_docblock->templates,
        );
        $this->assertSame(
            [
                'TB' => ['type' => '"c\\"=d"', 'line_number' => 12],
                'TC' => ['type' => '\'e\\\\\'', 'line_number' => 13],
                'TE' => ['type' => '', 'line_number' => 15],
            ],
            $class_docblock->template_defaults,
        );
    }

    /**
     * @return iterable<array-key, array{annotation: string, expected: array}>
     * @psalm-pure
     */
    public static function providerMethodAnnotation(): iterable
    {
        $data = [
            'foo()' => [
                'name' => 'foo',
                'returnType' => '',
                'is_static' => false,
                'params' => [],
            ],
            'foo($a)' => [
                'name' => 'foo',
                'returnType' => '',
                'is_static' => false,
                'params' => [
                    'a' => ['type' => ''],
                ],
            ],
            'string foo()' => [
                'name' => 'foo',
                'returnType' => 'string',
                'is_static' => false,
                'params' => [],
            ],
            'static string foo()' => [
                'name' => 'foo',
                'returnType' => 'string',
                'is_static' => true,
                'params' => [],
            ],
            'string foo(string $a, int $b)' => [
                'name' => 'foo',
                'returnType' => 'string',
                'is_static' => false,
                'params' => [
                    'a' => ['type' => 'string'],
                    'b' => ['type' => 'int'],
                ],
            ],
            'static string foo(string $a, int $b)' => [
                'name' => 'foo',
                'returnType' => 'string',
                'is_static' => true,
                'params' => [
                    'a' => ['type' => 'string'],
                    'b' => ['type' => 'int'],
                ],
            ],
            'static foo()' => [
                'name' => 'foo',
                'returnType' => 'static',
                'is_static' => false,
                'params' => [],
            ],
            'static static foo()' => [
                'name' => 'foo',
                'returnType' => 'static',
                'is_static' => true,
                'params' => [],
            ],
            'static foo(string $z)' => [
                'name' => 'foo',
                'returnType' => 'static',
                'is_static' => false,
                'params' => [
                    'z' => ['type' => 'string'],
                ],
            ],
            'static static foo(string $z)' => [
                'name' => 'foo',
                'returnType' => 'static',
                'is_static' => true,
                'params' => [
                    'z' => ['type' => 'string'],
                ],
            ],
            'self foo()' => [
                'name' => 'foo',
                'returnType' => 'MyClass',
                'is_static' => false,
                'params' => [],
            ],
            'static self foo()' => [
                'name' => 'foo',
                'returnType' => 'MyClass',
                'is_static' => true,
                'params' => [],
            ],
            'self foo(string $z)' => [
                'name' => 'foo',
                'returnType' => 'MyClass',
                'is_static' => false,
                'params' => [
                    'z' => ['type' => 'string'],
                ],
            ],
            'static self foo(string $z)' => [
                'name' => 'foo',
                'returnType' => 'MyClass',
                'is_static' => true,
                'params' => [
                    'z' => ['type' => 'string'],
                ],
            ],
            '(string|int)[] getArray()' => [
                'name' => 'getArray',
                'returnType' => 'array<array-key, int|string>',
                'is_static' => false,
                'params' => [],
            ],
            'static (string|int)[] getArray()' => [
                'name' => 'getArray',
                'returnType' => 'array<array-key, int|string>',
                'is_static' => true,
                'params' => [],
            ],
            '(callable() : string) getCallable()' => [
                'name' => 'getCallable',
                'returnType' => 'callable[impure]():string',
                'is_static' => false,
                'params' => [],
            ],
            'static (callable() : string) getCallable()' => [
                'name' => 'getCallable',
                'returnType' => 'callable[impure]():string',
                'is_static' => true,
                'params' => [],
            ],
            // Parenthesized union types in method parameters (issue #11730)
            'foo((int|string) $value)' => [
                'name' => 'foo',
                'returnType' => '',
                'is_static' => false,
                'params' => [
                    'value' => ['type' => 'int|string'],
                ],
            ],
            '$this lock((int|string) $value)' => [
                'name' => 'lock',
                'returnType' => 'static',
                'is_static' => false,
                'params' => [
                    'value' => ['type' => 'int|string'],
                ],
            ],
            'void bar((int|string) $x, int $y)' => [
                'name' => 'bar',
                'returnType' => 'void',
                'is_static' => false,
                'params' => [
                    'x' => ['type' => 'int|string'],
                    'y' => ['type' => 'int'],
                ],
            ],
            'static $this lock((int|string) $value)' => [
                'name' => 'lock',
                'returnType' => 'static',
                'is_static' => true,
                'params' => [
                    'value' => ['type' => 'int|string'],
                ],
            ],
            'baz((int|string) $x) : bool' => [
                'name' => 'baz',
                'returnType' => 'bool',
                'is_static' => false,
                'params' => [
                    'x' => ['type' => 'int|string'],
                ],
            ],
            // String literal union types in parentheses (Laravel-style)
            "\$this lock(('none'|'shared'|'default'|'exclusive') \$value)" => [
                'name' => 'lock',
                'returnType' => 'static',
                'is_static' => false,
                'params' => [
                    'value' => ['type' => 'string'],
                ],
            ],
            // Multiple parenthesized union parameters
            'void qux((int|string) $x, (bool|null) $y)' => [
                'name' => 'qux',
                'returnType' => 'void',
                'is_static' => false,
                'params' => [
                    'x' => ['type' => 'int|string'],
                    'y' => ['type' => 'bool|null'],
                ],
            ],
        ];

        $res = [];
        foreach ($data as $key => $item) {
            $res[$key] = [
                'annotation' => $key,
                'expected' => $item,
            ];
        }

        return $res;
    }

    #[DataProvider('providerMethodAnnotation')]
    public function testMethodAnnotation(string $annotation, array $expected): void
    {
        $full_content = <<<EOF
            <?php
            /**
             * @method $annotation
             */
            class MyClass {}
            EOF;

        $this->addFile('somefile.php', $full_content);

        $codebase = $this->project_analyzer->getCodebase();
        $codebase->scanFiles();

        $class_storage = $codebase->classlike_storage_provider->get('MyClass');
        $methods = $expected['is_static']
            ? $class_storage->pseudo_static_methods
            : $class_storage->pseudo_methods;
        $method = array_values($methods)[0];

        $actual = [
            'name' => $method->cased_name,
            'returnType' => (string) $method->return_type,
            'is_static' => $method->is_static,
            'params' => [],
        ];
        foreach ($method->params as $param) {
            $actual['params'][$param->name] = [
                'type' => (string) $param->type,
            ];
        }

        $this->assertEquals($expected, $actual);
    }
}
