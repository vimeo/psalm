<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeFinder;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Exception\UnpopulatedClasslikeException;
use Psalm\Internal\CacheMode;
use Psalm\Internal\Provider\ClassLikeStorageCacheProvider;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Issue\InvalidReturnStatement;
use Psalm\Issue\InvalidReturnType;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterClassLikeVisitInterface;
use Psalm\Plugin\EventHandler\BeforeAddIssueInterface;
use Psalm\Plugin\EventHandler\Event\AfterClassLikeVisitEvent;
use Psalm\Plugin\EventHandler\Event\BeforeAddIssueEvent;
use Psalm\PluginRegistrationSocket;
use Psalm\Type;

use function array_keys;
use function array_map;
use function array_values;
use function get_class;
use function getcwd;

use const DIRECTORY_SEPARATOR;

final class CodebaseTest extends TestCase
{
    private Codebase $codebase;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->codebase = $this->project_analyzer->getCodebase();
    }

    #[DataProvider('typeContainments')]
    #[Test]
    public function isTypeContainedByType(string $input, string $container, bool $expected): void
    {
        $input = Type::parseString($input);
        $container = Type::parseString($container);

        $this->assertSame(
            $expected,
            $this->codebase->isTypeContainedByType($input, $container),
            'Expected ' . $input->getId() . ($expected ? ' ' : ' not ')
            . 'to be contained in ' . $container->getId(),
        );
    }

    /**
     * @return iterable<int,array{string,string,bool}>
     * @psalm-pure
     */
    public static function typeContainments(): iterable
    {
        yield ['int', 'int|string', true];
        yield ['int|string', 'int', false];

        // This fails with 'could not get class storage' :(

        // yield ['RuntimeException', 'Exception', true];
        // yield ['Exception', 'RuntimeException', false];
    }

    #[DataProvider('typeIntersections')]
    #[Test]
    public function canTypeBeContainedByType(string $input, string $container, bool $expected): void
    {
        $input = Type::parseString($input);
        $container = Type::parseString($container);

        $this->assertSame(
            $expected,
            $this->codebase->canTypeBeContainedByType($input, $container),
            'Expected ' . $input->getId() . ($expected ? ' ' : ' not ')
            . 'to be contained in ' . $container->getId(),
        );
    }

    /**
     * @return iterable<int,array{string,string,bool}>
     * @psalm-pure
     */
    public static function typeIntersections(): iterable
    {
        yield ['int', 'int|string', true];
        yield ['int|string', 'int', true];
        yield ['int|string', 'string|float', true];
        yield ['int', 'string', false];
        yield ['int|string', 'array|float', false];
    }

    /**
     * @param array{string,string} $expected
     */
    #[DataProvider('iterableParams')]
    #[Test]
    public function getKeyValueParamsForTraversableObject(string $input, array $expected): void
    {
        [$input] = array_values(Type::parseString($input)->getAtomicTypes());

        $expected_key_type = Type::parseString($expected[0]);
        $expected_value_type = Type::parseString($expected[1]);

        $actual = $this->codebase->getKeyValueParamsForTraversableObject($input);

        $this->assertTrue(
            $expected_key_type->equals($actual[0]),
            'Expected ' . $input->getId() . ' to have ' . $expected_key_type
            . ' but got ' . $actual[0]->getId(),
        );

        $this->assertTrue(
            $expected_value_type->equals($actual[1]),
            'Expected ' . $input->getId() . ' to have ' . $expected_value_type
            . ' but got ' . $actual[1]->getId(),
        );
    }

    /**
     * @return iterable<int,array{string,array{string,string}}>
     * @psalm-pure
     */
    public static function iterableParams(): iterable
    {
        yield ['iterable<int,string>', ['int', 'string']];
        yield ['iterable<int|string,bool|float>', ['int|string', 'bool|float']];
    }

    #[Test]
    public function dependentClasslikesIncludeTransitiveDependents(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
                namespace Psalm\CurrentTest;
                interface I {}
                interface J {}
                class A implements I, J {}
                class B extends A {}
                class C extends A {}
                class D extends B {}
                class E extends C {}
            ',
        );

        $this->analyzeFile('somefile.php', new Context);

        $ns = 'psalm\\currenttest\\';
        $provider = $this->codebase->classlike_storage_provider;

        self::assertSame(
            [$ns . 'b', $ns . 'c', $ns . 'd', $ns . 'e'],
            array_keys($provider->get($ns . 'a')->dependent_classlikes),
        );
        self::assertSame(
            [$ns . 'a', $ns . 'b', $ns . 'c', $ns . 'd', $ns . 'e'],
            array_keys($provider->get($ns . 'i')->dependent_classlikes),
        );
        self::assertSame(
            [$ns . 'a', $ns . 'b', $ns . 'c', $ns . 'd', $ns . 'e'],
            array_keys($provider->get($ns . 'j')->dependent_classlikes),
        );
        self::assertSame([$ns . 'd'], array_keys($provider->get($ns . 'b')->dependent_classlikes));
    }

    #[Test]
    public function customMetadataIsPersisted(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
                namespace Psalm\CurrentTest;
                abstract class A {}
                interface I {}
                class C extends A implements I
                {
                    /** @var string */
                    private $prop = "";

                    /**
                     * @psalm-mutation-free
                     *  
                     * @return void
                     */
                    public function m(int $_i = 1) {}
                }
            ',
        );
        $hook = new class implements AfterClassLikeVisitInterface {
            /**
             * @return void
             * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint
             */
            #[Override]
            public static function afterClassLikeVisit(AfterClassLikeVisitEvent $event)
            {
                $stmt = $event->getStmt();
                $storage = $event->getStorage();
                $codebase = $event->getCodebase();
                if ($storage->name === 'Psalm\\CurrentTest\\C' && $stmt instanceof Class_) {
                    $storage->custom_metadata['fqcn'] = (string)($stmt->getAttribute('namespacedName') ?? $stmt->name);
                    $storage->custom_metadata['extends'] = $stmt->extends instanceof Name
                        ? (string)$stmt->extends->getAttribute('resolvedName')
                        : '';
                    $storage->custom_metadata['implements'] = array_map(
                        static fn(Name $aspect): string => (string)$aspect->getAttribute('resolvedName'),
                        $stmt->implements,
                    );
                    $storage->custom_metadata['a'] = 'b';
                    $storage->methods['m']->custom_metadata['c'] = 'd';
                    $storage->properties['prop']->custom_metadata['e'] = 'f';
                    $storage->methods['m']->params[0]->custom_metadata['g'] = 'h';
                    $codebase->file_storage_provider->get('somefile.php')->custom_metadata['i'] = 'j';
                }
            }
        };
        (new PluginRegistrationSocket($this->codebase->config, $this->codebase))
            ->registerHooksFromClass(get_class($hook));
        $this->codebase->classlike_storage_provider->cache = new ClassLikeStorageCacheProvider($this->codebase->config, '', CacheMode::Memory);

        $this->analyzeFile('somefile.php', new Context);

        $fixtureNamespace = 'Psalm\\CurrentTest\\';
        // drops the storages from memory only, the cache has to still hold them
        ClassLikeStorageProvider::deleteAll();
        $this->codebase->exhumeClassLikeStorage($fixtureNamespace . 'C', 'somefile.php');

        $class_storage = $this->codebase->classlike_storage_provider->get($fixtureNamespace . 'C');
        $file_storage = $this->codebase->file_storage_provider->get('somefile.php');

        self::assertSame($fixtureNamespace . 'C', $class_storage->custom_metadata['fqcn']);
        self::assertSame($fixtureNamespace . 'A', $class_storage->custom_metadata['extends']);
        self::assertSame([$fixtureNamespace . 'I'], $class_storage->custom_metadata['implements']);
        self::assertSame('b', $class_storage->custom_metadata['a']);
        self::assertSame('d', $class_storage->methods['m']->custom_metadata['c']);
        self::assertSame('f', $class_storage->properties['prop']->custom_metadata['e']);
        self::assertSame('h', $class_storage->methods['m']->params[0]->custom_metadata['g']);
        self::assertSame('j', $file_storage->custom_metadata['i']);
    }

    /**
     * @param class-string<FunctionLike> $function_like_class
     */
    #[DataProvider('functionLikesWithSignatureTypes')]
    #[Test]
    public function signatureTypesOfFunctionLikesAreNameResolved(string $function_like_class): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
                namespace Psalm\CurrentTest;

                use Some\Vendor\Param;
                use Some\Vendor\Result;

                function f(Param $p): Result { return new Result(); }
                $c = function (Param $p): Result { return new Result(); };
                $a = fn (Param $p): Result => new Result();',
        );

        $statements = $this->codebase->getStatementsForFile('somefile.php');

        $function_like = (new NodeFinder())->findFirstInstanceOf($statements, $function_like_class);

        self::assertInstanceOf($function_like_class, $function_like);

        $param_type = $function_like->getParams()[0]->type;
        $return_type = $function_like->getReturnType();

        self::assertInstanceOf(Name::class, $param_type);
        self::assertInstanceOf(Name::class, $return_type);
        self::assertSame('Some\\Vendor\\Param', $param_type->getAttribute('resolvedName'));
        self::assertSame('Some\\Vendor\\Result', $return_type->getAttribute('resolvedName'));
    }

    /**
     * @return iterable<string, array{class-string<FunctionLike>}>
     * @psalm-pure
     */
    public static function functionLikesWithSignatureTypes(): iterable
    {
        yield 'function' => [Function_::class];
        yield 'closure' => [Closure::class];
        yield 'arrow function' => [ArrowFunction::class];
    }

    /**
     * @param class-string<FunctionLike> $function_like_class
     * @param positive-int $name_count
     */
    #[DataProvider('functionLikesWithCompositeSignatureTypes')]
    #[Test]
    public function compositeSignatureTypesOfFunctionLikesAreNameResolved(
        string $function_like_class,
        int $name_count,
    ): void {
        $this->addFile(
            'somefile.php',
            '<?php
                namespace Psalm\CurrentTest;

                use Some\Vendor\Param;
                use Some\Vendor\Result;

                function f(Param|Result $p): (Param&Result)|Result { return $p; }
                $c = function (Param&Result $p): Param|Result { return $p; };
                $a = fn (Param|Result $p): (Param&Result)|Result => $p;
                final class C { public function m(Param&Result $p): Param|Result { return $p; } }',
        );

        $statements = $this->codebase->getStatementsForFile('somefile.php');

        $function_like = (new NodeFinder())->findFirstInstanceOf($statements, $function_like_class);

        self::assertInstanceOf($function_like_class, $function_like);

        $param_type = $function_like->getParams()[0]->type;
        $return_type = $function_like->getReturnType();

        self::assertNotNull($param_type);
        self::assertNotNull($return_type);

        $names = (new NodeFinder())->findInstanceOf([$param_type, $return_type], Name::class);

        self::assertCount($name_count, $names);

        foreach ($names as $name) {
            self::assertContains(
                $name->getAttribute('resolvedName'),
                ['Some\\Vendor\\Param', 'Some\\Vendor\\Result'],
                $name->toString(),
            );
        }
    }

    /**
     * @return iterable<string, array{class-string<FunctionLike>, positive-int}>
     * @psalm-pure
     */
    public static function functionLikesWithCompositeSignatureTypes(): iterable
    {
        yield 'function' => [Function_::class, 5];
        yield 'closure' => [Closure::class, 4];
        yield 'arrow function' => [ArrowFunction::class, 5];
        yield 'method' => [ClassMethod::class, 4];
    }

    /**
     * @param array<int, string> $arg_types
     */
    #[DataProvider('receiverTaintPaths')]
    #[Test]
    public function getReceiverTaintPath(string $method_id, array $arg_types, ?string $expected): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
                namespace Psalm\CurrentTest;

                /**
                 * @extends \ArrayObject<string, string>
                 * @psalm-capabilities read-props|write-this-props
                 */
                class Items extends \ArrayObject {}

                /** @psalm-capabilities read-props|write-this-props */
                class MoreItems extends Items {}',
        );

        $this->analyzeFile('somefile.php', new Context);

        self::assertSame(
            $expected,
            $this->codebase->getReceiverTaintPath($method_id, array_map(Type::parseString(...), $arg_types)),
        );
    }

    /**
     * @return iterable<string, array{string, array<int, string>, ?string}>
     * @psalm-pure
     */
    public static function receiverTaintPaths(): iterable
    {
        yield 'whole object' => ['ArrayObject::getArrayCopy', [], '='];
        yield 'element' => ['ArrayIterator::current', [], 'arrayvalue-fetch'];
        yield 'key' => ['ArrayIterator::key', [], 'arraykey-fetch'];
        yield 'element of an unknown key' => ['ArrayObject::offsetGet', ['string'], 'arrayvalue-fetch'];
        yield 'element of a literal string key' => [
            'ArrayObject::offsetGet',
            ["'a'"],
            "arrayvalue-fetch-'a'",
        ];
        yield 'element of a literal int key' => [
            'ArrayObject::offsetGet',
            ['1'],
            "arrayvalue-fetch-'1'",
        ];
        yield 'literal argument that is not a key' => [
            'ArrayIterator::current',
            ["'a'"],
            'arrayvalue-fetch',
        ];
        yield 'method of a parent class' => ['Psalm\\CurrentTest\\Items::getArrayCopy', [], '='];
        yield 'method of a grandparent class' => [
            'Psalm\\CurrentTest\\MoreItems::offsetGet',
            ["'a'"],
            "arrayvalue-fetch-'a'",
        ];
        yield 'method keeping its arguments' => ['ArrayObject::offsetSet', [], null];
        yield 'method giving back nothing' => ['ArrayObject::count', [], null];
        yield 'method of a class with no paths' => ['Psalm\\CurrentTest\\Items::undefined', [], null];
        yield 'yielded value' => ['Generator::current', [], 'arrayvalue-fetch'];
        yield 'yielded value after a send' => ['Generator::send', ["'a'"], 'arrayvalue-fetch'];
        yield 'yielded key' => ['Generator::key', [], 'arraykey-fetch'];
        yield 'returned value' => ['Generator::getReturn', [], 'return'];
        yield 'generator method giving back nothing' => ['Generator::rewind', [], null];
    }

    #[Test]
    public function classExtendsRejectsUnpopulatedClasslikes(): void
    {
        $this->codebase->classlike_storage_provider->create('A');
        $this->codebase->classlike_storage_provider->create('B');

        $this->expectException(UnpopulatedClasslikeException::class);

        $this->codebase->classExtends('A', 'B');
    }

    #[Test]
    public function addingCodeIssueIsIntercepted(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
                namespace Psalm\CurrentTest;
                /** @psalm-pure */
                function invalidReturnType(int $value): string
                {
                    return $value;
                }
                echo invalidReturnType(123);
            ',
        );

        $eventHandler = new class implements BeforeAddIssueInterface
        {
            /**
             * @psalm-mutation-free
             */
            #[Override]
            public static function beforeAddIssue(BeforeAddIssueEvent $event): ?bool
            {
                $issue = $event->getIssue();
                if ($issue->code_location->file_path !== 'somefile.php') {
                    return null;
                }
                if ($issue instanceof InvalidReturnStatement && $event->isFixable() === false) {
                    return false;
                } elseif ($issue instanceof InvalidReturnType && $event->isFixable() === true) {
                    return false;
                }
                return null;
            }
        };

        (new PluginRegistrationSocket($this->codebase->config, $this->codebase))
            ->registerHooksFromClass(get_class($eventHandler));

        $this->analyzeFile('somefile.php', new Context);
        self::assertSame(0, IssueBuffer::getErrorCount());
    }
    #[Test]
    public function addingCodeIssueIsMarkedAsRedundant(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('UnusedPsalmSuppress');

        $this->addFile(
            (string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php',
            '<?php
                namespace Psalm\CurrentTest;

                /**
                 * @psalm-suppress InvalidReturnType
                 * @psalm-pure
                 */
                function invalidReturnType(int $value): string
                {
                    /** @psalm-suppress InvalidReturnStatement */
                    return $value;
                }
                echo invalidReturnType(123);
            ',
        );
        $eventHandler = new class implements BeforeAddIssueInterface
        {
            #[Override]
            public static function beforeAddIssue(BeforeAddIssueEvent $event): ?bool
            {
                $issue = $event->getIssue();
                if ($issue->code_location->file_path !== (string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php') {
                    return null;
                }
                if ($issue instanceof InvalidReturnStatement && $event->isFixable() === false) {
                    return false;
                } elseif ($issue instanceof InvalidReturnType && $event->isFixable() === true) {
                    return false;
                }
                return null;
            }
        };

        (new PluginRegistrationSocket($this->codebase->config, $this->codebase))
            ->registerHooksFromClass(get_class($eventHandler));

        $this->analyzeFile(
            (string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php',
            new Context,
        );
    }
}
