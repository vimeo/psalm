<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use PhpParser\Node\Expr\MethodCall;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\Comparator\CallableTypeComparator;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Union;

/**
 * The return types of `Closure::fromCallable()`, `Closure::bind()` and `bindTo()`: the closure
 * they create, or rebind, keeps its signature and purity.
 *
 * @internal
 */
final class ClosureReturnTypeProvider implements MethodReturnTypeProviderInterface
{
    /**
     * @psalm-pure
     */
    #[Override]
    public static function getClassLikeNames(): array
    {
        return ['Closure'];
    }

    #[Override]
    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event): ?Union
    {
        $source = $event->getSource();
        $method_name_lowercase = $event->getMethodNameLowercase();
        $call_args = $event->getCallArgs();
        if (!$source instanceof StatementsAnalyzer) {
            return null;
        }

        $type_provider = $source->getNodeTypeProvider();
        $codebase = $source->getCodebase();
        $context = $event->getContext();

        if ($method_name_lowercase === 'fromcallable') {
            $closure_types = [];

            if (isset($call_args[0])
                && ($input_type = $type_provider->getType($call_args[0]->value))
            ) {
                foreach ($input_type->getAtomicTypes() as $atomic_type) {
                    $candidate_callable = CallableTypeComparator::getCallableFromAtomic(
                        $codebase,
                        $atomic_type,
                        null,
                        $source,
                        $context,
                        true,
                    );

                    if ($candidate_callable) {
                        $closure_types[] = new TClosure(
                            $candidate_callable->params,
                            $candidate_callable->return_type,
                            $candidate_callable->purity,
                        );
                    } else {
                        return Type::getClosure();
                    }
                }
            }

            if ($closure_types) {
                return TypeCombiner::combine($closure_types, $codebase);
            }

            return Type::getClosure();
        }

        if ($method_name_lowercase === 'bind' || $method_name_lowercase === 'bindto') {
            $stmt = $event->getStmt();

            $closure = $method_name_lowercase === 'bindto'
                ? ($stmt instanceof MethodCall ? $stmt->var : null)
                : ($call_args[0]->value ?? null);

            $closure_type = $closure !== null ? $type_provider->getType($closure) : null;

            if ($closure_type === null) {
                return null;
            }

            $closure_types = [];

            foreach ($closure_type->getAtomicTypes() as $atomic_type) {
                if (!$atomic_type instanceof TClosure) {
                    return null;
                }

                $closure_types[] = $atomic_type;
            }

            // null when the closure cannot be bound to the new $this or scope
            return new Union([...$closure_types, new TNull()]);
        }

        return null;
    }
}
