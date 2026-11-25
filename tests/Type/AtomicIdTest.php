<?php

declare(strict_types=1);

namespace Psalm\Tests\Type;

use LogicException;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Storage\Capabilities;
use Psalm\Tests\TestCase;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TCapabilities;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TClosedResource;
use Psalm\Type\Atomic\TDependentGetDebugType;
use Psalm\Type\Atomic\TDependentGetType;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntMaskOf;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TKeyOf;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TPropertiesOf;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateIndexedAccess;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplatePropertiesOf;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Atomic\TTypeAlias;
use Psalm\Type\Atomic\TValueOf;
use Psalm\Type\Atomic\TVoid;
use Psalm\Type\Union;
use ReflectionClass;

use function basename;
use function class_exists;
use function glob;

final class AtomicIdTest extends TestCase
{
    #[DataProvider('providerIds')]
    public function testId(Atomic $atomic, string $id, string $inexact_id): void
    {
        $this->assertSame($id, $atomic->getId());
        $this->assertSame($inexact_id, $atomic->getId(false));
    }

    /**
     * @return iterable<string, array{Atomic, string, string}>
     * @psalm-pure
     */
    public static function providerIds(): iterable
    {
        $const = new TClassConstant('Foo', 'BAR');
        $template = new TTemplateParam('T', Type::getObject(), 'Foo');

        yield 'array-key' => [new TArrayKey(), 'array-key', 'array-key'];
        yield 'bool' => [new TBool(), 'bool', 'bool'];
        yield 'callableObject' => [new TCallableObject(), 'callable-object', 'callable-object'];
        yield 'callableObjectWithSignature' => [
            new TCallableObject(false, new TCallable([], Type::getInt())),
            'callable-object():int',
            'callable-object():int',
        ];
        yield 'callableString' => [new TCallableString(), 'callable-string', 'callable-string'];
        yield 'capabilities' => [new TCapabilities(Capabilities::MUTATION_FREE), 'read-props', 'read-props'];
        yield 'closedResource' => [new TClosedResource(), 'closed-resource', 'closed-resource'];
        yield 'getDebugType' => [
            new TDependentGetDebugType('$x'),
            'get-debug-type-of<$x>',
            'get-debug-type-of<$x>',
        ];
        yield 'getType' => [new TDependentGetType('$x'), 'string', 'string'];
        yield 'false' => [new TFalse(), 'false', 'false'];
        yield 'float' => [new TFloat(), 'float', 'float'];
        yield 'int' => [new TInt(), 'int', 'int'];
        yield 'intMaskOfClassConstant' => [
            new TIntMaskOf($const),
            'int-mask-of<class-constant(Foo::BAR)>',
            'int-mask-of<class-constant(Foo::BAR)>',
        ];
        yield 'intMaskOfKeyOf' => [
            new TIntMaskOf(new TKeyOf(new Union([$const]))),
            'int-mask-of<key-of<Foo::BAR>>',
            'int-mask-of<key-of<Foo::BAR>>',
        ];
        yield 'intMaskOfValueOf' => [
            new TIntMaskOf(new TValueOf(new Union([$const]))),
            'int-mask-of<value-of<Foo::BAR>>',
            'int-mask-of<value-of<Foo::BAR>>',
        ];
        yield 'intRange' => [new TIntRange(1, 5), 'int<1, 5>', 'int<1, 5>'];
        yield 'intRangeUnbounded' => [new TIntRange(null, null), 'int<min, max>', 'int<min, max>'];
        yield 'keyOf' => [new TKeyOf(new Union([$template])), 'key-of<T:Foo as object>', 'key-of<T:Foo as object>'];
        yield 'mixed' => [new TMixed(), 'mixed', 'mixed'];
        yield 'never' => [new TNever(), 'never', 'never'];
        yield 'null' => [new TNull(), 'null', 'null'];
        yield 'numeric' => [new TNumeric(), 'numeric', 'numeric'];
        yield 'object' => [new TObject(), 'object', 'object'];
        yield 'propertiesOf' => [
            new TPropertiesOf(new TNamedObject('Foo'), null),
            'properties-of<Foo>',
            'properties-of<Foo>',
        ];
        yield 'publicPropertiesOf' => [
            new TPropertiesOf(new TNamedObject('Foo'), TPropertiesOf::VISIBILITY_PUBLIC),
            'public-properties-of<Foo>',
            'public-properties-of<Foo>',
        ];
        yield 'resource' => [new TResource(), 'resource', 'resource'];
        yield 'scalar' => [new TScalar(), 'scalar', 'scalar'];
        yield 'string' => [new TString(), 'string', 'string'];
        yield 'templateIndexedAccess' => [new TTemplateIndexedAccess('T', 'K', 'Foo'), 'T[K]', 'T[K]'];
        yield 'templatePropertiesOf' => [
            new TTemplatePropertiesOf('T', 'Foo', $template, null),
            'properties-of<T:Foo as object>',
            'properties-of<T>',
        ];
        yield 'templatePrivatePropertiesOf' => [
            new TTemplatePropertiesOf('T', 'Foo', $template, TPropertiesOf::VISIBILITY_PRIVATE),
            'private-properties-of<T:Foo as object>',
            'private-properties-of<T>',
        ];
        yield 'true' => [new TTrue(), 'true', 'true'];
        yield 'typeAlias' => [new TTypeAlias('Foo', 'Bar'), 'type-alias(Foo::Bar)', 'type-alias(Foo::Bar)'];
        yield 'valueOf' => [
            new TValueOf(new Union([$template])),
            'value-of<T:Foo as object>',
            'value-of<T:Foo as object>',
        ];
        yield 'void' => [new TVoid(), 'void', 'void'];
    }

    public function testIdMustBeWrittenByADirectSubclass(): void
    {
        $atomic = new /** @psalm-immutable */ class extends Atomic {
            /** @psalm-pure */
            #[Override]
            public function getKey(bool $include_extra = true): string
            {
                return 'foo';
            }

            /** @psalm-pure */
            #[Override]
            public function toPhpString(
                ?string $namespace,
                array $aliased_classes,
                ?string $this_class,
                int $analysis_php_version_id,
            ): ?string {
                return null;
            }

            /** @psalm-pure */
            #[Override]
            public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
            {
                return false;
            }
        };

        $this->expectException(LogicException::class);
        $atomic->getId();
    }

    public function testIdIsDeclaredWithTheKey(): void
    {
        $files = glob(__DIR__ . '/../../src/Psalm/Type/Atomic/*.php');
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $name = 'Psalm\\Type\\Atomic\\' . basename($file, '.php');
            if (!class_exists($name)) {
                continue;
            }

            $class = new ReflectionClass($name);
            if ($class->isAbstract() || !$class->isSubclassOf(Atomic::class)) {
                continue;
            }

            $id_class = $class->getMethod('getId')->getDeclaringClass();
            $key_class = $class->getMethod('getKey')->getDeclaringClass()->getName();

            $this->assertTrue(
                $id_class->getName() === $key_class || $id_class->isSubclassOf($key_class),
                $class->getName() . ' takes its id from ' . $id_class->getName() . ' but its key from ' . $key_class,
            );
        }
    }
}
