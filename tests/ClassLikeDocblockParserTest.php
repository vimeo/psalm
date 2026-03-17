<?php

declare(strict_types=1);

namespace Psalm\Tests;

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

    /**
     * @return iterable<array-key, array{annotation: string, expected: array}>
     * @psalm-pure
     */
    public function providerMethodAnnotation(): iterable
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
                'returnType' => 'impure-callable():string',
                'is_static' => false,
                'params' => [],
            ],
            'static (callable() : string) getCallable()' => [
                'name' => 'getCallable',
                'returnType' => 'impure-callable():string',
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

    /**
     * @dataProvider providerMethodAnnotation
     */
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
