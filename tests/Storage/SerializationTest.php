<?php

declare(strict_types=1);

namespace Psalm\Tests\Storage;

use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Psalm\CodeLocation;
use Psalm\CodeLocation\Raw;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Scanner\UnresolvedConstant\ClassConstant;
use Psalm\Internal\Scanner\UnresolvedConstant\ScalarValue;
use Psalm\Internal\Scanner\UnresolvedConstant\UnresolvedAdditionOp;
use Psalm\Internal\Type\TypeAlias\LinkableTypeAlias;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\TaintedHtml;
use Psalm\Storage\Assertion\HasArrayKey;
use Psalm\Storage\Assertion\IsType;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FileStorage;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\MethodStorage;
use Psalm\Storage\PropertyStorage;
use Psalm\Tests\TestCase;
use Psalm\Type;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

use function array_map;
use function file_get_contents;
use function igbinary_serialize;
use function igbinary_unserialize;
use function is_array;
use function is_object;
use function memory_get_usage;
use function range;
use function serialize;
use function unserialize;

final class SerializationTest extends TestCase
{
    /**
     * @return iterable<string, array{Closure(): object}>
     */
    public static function providerObjects(): iterable
    {
        yield 'trait used by the parent' => [static fn(): object => new SerializationFixture(1)];
        yield 'trait used by the parent and the class' => [
            static fn(): object => new SerializationFixtureUsingTraitAgain(1),
        ];
        yield 'MethodStorage' => [static function (): object {
            $storage = new MethodStorage();
            $storage->cased_name = 'foo';
            $storage->defining_fqcln = 'Foo';
            $storage->visibility = ClassLikeAnalyzer::VISIBILITY_PROTECTED;
            $storage->is_static = true;
            $storage->setParams([new FunctionLikeParameter('a', true)]);
            return $storage;
        }];
        yield 'ClassLikeStorage' => [static function (): object {
            $storage = new ClassLikeStorage('Foo');
            $storage->parent_class = 'bar';
            $storage->declaring_method_ids['foo'] = new MethodIdentifier('Foo', 'foo');
            $storage->properties['p'] = new PropertyStorage();
            return $storage;
        }];
        yield 'FileStorage' => [static function (): object {
            $storage = new FileStorage('/a.php');
            $storage->classlikes_in_file = ['foo' => 'Foo'];
            return $storage;
        }];
        yield 'CodeLocation' => [self::location(...)];
        yield 'unresolved constant' => [static fn(): object => new UnresolvedAdditionOp(
            new ScalarValue(1),
            new ClassConstant('Foo', 'BAR'),
        )];
        yield 'type alias' => [static fn(): object => new LinkableTypeAlias('Foo', 'Bar', 1, 2, 3)];
        yield 'docblock issue' => [static fn(): object => new InvalidDocblock('message', self::location())];
        yield 'issue with a readonly property' => [
            static fn(): object => new TaintedHtml('message', self::location(), [], 'journey'),
        ];
        yield 'atomic' => [static fn(): object => new TNamedObject('Foo', true)];
        yield 'assertion' => [static fn(): object => new IsType(new TLiteralString('a'))];
        yield 'Union' => [static function (): object {
            $type = new Union([new TInt(), new TLiteralString('a'), new TNamedObject('Foo')], ['by_ref' => true]);
            // fills the caches of the ids
            self::assertSame("'a'|Foo|int", $type->getId());
            self::assertSame("'a'|Foo|int", $type->getId(true));

            return $type;
        }];
        yield 'array shape' => [static fn(): object => Type::parseString('array{a: int, b?: list<string>}')];
        yield 'assertion with a readonly property' => [static fn(): object => new HasArrayKey('a')];
    }

    /**
     * A location with the properties it computes from the file
     */
    private static function location(): CodeLocation
    {
        $location = (new Raw((string) file_get_contents(__FILE__), __FILE__, 'SerializationTest.php', 200, 210))
            ->setCommentLine(2);
        self::assertNotSame('', $location->getSnippet());

        return $location;
    }

    /**
     * @param Closure(): object $factory
     */
    #[DataProvider('providerObjects')]
    public function testSerializeKeepsProperties(Closure $factory): void
    {
        $object = $factory();

        $this->assertSame(self::export($object), self::export(unserialize(serialize($object))));
    }

    /**
     * @param Closure(): object $factory
     */
    #[DataProvider('providerObjects')]
    #[RequiresPhpExtension('igbinary')]
    public function testIgbinarySerializeKeepsProperties(Closure $factory): void
    {
        $object = $factory();
        $serialized = igbinary_serialize($object);
        $this->assertIsString($serialized);

        $this->assertSame(self::export($object), self::export(igbinary_unserialize($serialized)));
    }

    /**
     * @return iterable<string, array{SerializationFixtureParent}>
     * @psalm-pure
     */
    public static function providerFixtures(): iterable
    {
        yield 'trait used by the parent' => [new SerializationFixture(1)];
        yield 'trait used by the parent and the class' => [new SerializationFixtureUsingTraitAgain(1)];
    }

    #[DataProvider('providerFixtures')]
    public function testFixtureKeepsDescribedValues(SerializationFixtureParent $fixture): void
    {
        $unserialized = unserialize(serialize($fixture));

        $this->assertInstanceOf($fixture::class, $unserialized);
        $this->assertSame($fixture->describe(), $unserialized->describe());
        $this->assertFalse($unserialized->describe()['uninitialized_is_set']);
    }

    /**
     * Without __serialize() igbinary builds a table of each object's properties, which the object keeps
     * (https://github.com/php/php-src/issues/10126): 376 bytes or more, 8 KB for a MethodStorage. The main
     * process writes every storage it scans to the cache, and keeps using them.
     *
     * @param Closure(): object $factory
     */
    #[DataProvider('providerObjects')]
    #[RequiresPhpExtension('igbinary')]
    public function testIgbinarySerializeLeavesNoPropertyTable(Closure $factory): void
    {
        $objects = array_map(static fn(int $_i): object => $factory(), range(1, 1_000));

        $before = memory_get_usage();
        $serialized = igbinary_serialize($objects);
        unset($serialized);

        $this->assertLessThan(64, (memory_get_usage() - $before) / 1_000);
    }

    /**
     * The values of the initialized properties of an object and of the objects it holds, by class
     *
     * @psalm-pure
     */
    private static function export(mixed $value): mixed
    {
        if (is_object($value)) {
            return [$value::class => self::export((array) $value)];
        }

        if (is_array($value)) {
            return array_map(self::export(...), $value);
        }

        return $value;
    }
}
