<?php
namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Type\Comparator\CallableTypeComparator;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Type;

class ClosureFromCallableReturnTypeProvider implements \Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface
{
    public static function getClassLikeNames() : array
    {
        return ['Closure'];
    }

    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event): ?Type\Union
    {
        $source = $event->getSource();
        $method_name_lowercase = $event->getMethodNameLowercase();
        $call_args = $event->getCallArgs();
        if (!$source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
            return null;
        }

        $type_provider = $source->getNodeTypeProvider();
        $codebase = $source->getCodebase();

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
                        $source
                    );

                    if ($candidate_callable) {
                        $closure_types[] = new Type\Atomic\TClosure(
                            'Closure',
                            $candidate_callable->params,
                            $candidate_callable->return_type,
                            $candidate_callable->is_pure
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

        return null;
    }
}
