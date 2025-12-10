<?php

declare(strict_types=1);

namespace Psalm\Internal\StringInterpreter;

use AssertionError;
use Exception;
use InvalidArgumentException;
use LogicException;
use Override;
use Psalm\Exception\CodeException;
use Psalm\Exception\TypeParseTreeException;
use Psalm\Exception\UnpopulatedClasslikeException;
use Psalm\Exception\UnresolvableConstantException;
use Psalm\Internal\Codebase\Reflection;
use Psalm\Plugin\EventHandler\Event\StringInterpreterEvent;
use Psalm\Plugin\EventHandler\StringInterpreterInterface;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralString;
use ReflectionClass;
use ReflectionException;
use UnexpectedValueException;

use function class_exists;
use function enum_exists;
use function interface_exists;
use function ltrim;
use function strtolower;
use function trait_exists;

/**
 * @internal
 */
final class ClassStringInterpreter implements StringInterpreterInterface
{
    /**
     * @throws AssertionError
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws CodeException
     * @throws TypeParseTreeException
     * @throws UnpopulatedClasslikeException
     * @throws UnresolvableConstantException
     * @throws UnexpectedValueException
     */
    #[Override]
    public static function getTypeFromValue(StringInterpreterEvent $event): ?TLiteralString
    {
        $value = $event->getValue();
        if ($value === '') {
            return null;
        }

        $value = ltrim($value, '\\');
        $codebase = $event->getCodebase();
        $value_lc = strtolower($value);

        // Bunch of _exists() to make $value match the type class-string|object|trait-string
        if ((class_exists($value) || interface_exists($value) || enum_exists($value) || trait_exists($value)) &&
            $codebase->classlikes->doesClassLikeExist($value_lc)) {
            if (!$codebase->classlike_storage_provider->has($value)) {
                $reflection = new Reflection($codebase->classlike_storage_provider, $codebase);
                try {
                    $reflection->registerClass(new ReflectionClass($value));
                } catch (ReflectionException) {
                    // `new ReflectionClass()` only throws if the class, interface, enum, or trait does not exist.
                    // Which we check, so this should never happen.
                    return null;
                }
            }

            return new TLiteralClassString($value);
        }

        return null;
    }
}
