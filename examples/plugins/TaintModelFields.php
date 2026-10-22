<?php

declare(strict_types=1);

namespace Psalm\Example\Plugin;

use Override;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use Psalm\CodeLocation;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\TaintKind;

use function str_ends_with;

/**
 * Follows the fields of the models of an ORM, `app\Model`, from `$model->set('field', $value)` to
 * `$model->get('field')` through the node of the field, `Model::$field`, and reports the values set to the fields
 * whose name ends with `_html`, which the ORM's templates output without escaping them.
 */
final class TaintModelFields implements AfterExpressionAnalysisInterface
{
    #[Override]
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        $call = $event->getExpr();
        if (!$call instanceof MethodCall || !$call->name instanceof Identifier || $call->isFirstClassCallable()) {
            return null;
        }

        $source = $event->getStatementsSource();
        $codebase = $event->getCodebase();
        if (!$codebase->tracksTaintFlow($source)) {
            return null;
        }

        $node_types = $source->getNodeTypeProvider();
        $receiver_type = $node_types->getType($call->var);
        $receiver = $receiver_type !== null && $receiver_type->isSingle() ? $receiver_type->getSingleAtomic() : null;
        $args = $call->getArgs();
        $field_type = isset($args[0]) ? $node_types->getType($args[0]->value) : null;
        if (!$receiver instanceof TNamedObject
            || !$codebase->classExtendsOrImplements($receiver->value, 'app\Model')
            || $field_type === null
            || !$field_type->isSingleStringLiteral()
        ) {
            return null;
        }

        $field = $field_type->getSingleStringLiteral()->value;
        $field_node = $codebase->getTaintPropertyNode($receiver->value . '::$' . $field);
        $location = new CodeLocation($source, $call);
        $method = $call->name->toLowerString();
        if ($method === 'set' && isset($args[1])) {
            $value = $codebase->getTaintNode($source, 'model field', $location, $node_types->getType($args[1]->value));
            $codebase->addTaintPath($source, $value, $field_node, 'property-assignment');

            if (str_ends_with($field, '_html')) {
                $sink = $codebase->addTaintSink('raw model field', $location, TaintKind::INPUT_HTML);
                if ($sink !== null) {
                    $codebase->addTaintPath($source, $value, $sink);
                }
            }
        } elseif ($method === 'get') {
            $type = $node_types->getType($call);
            if ($type === null) {
                return null;
            }

            $read = $codebase->getTaintNode($source, 'model field', $location);
            $codebase->addTaintPath($source, $field_node, $read, 'property-fetch');
            $node_types->setType($call, $type->addParentNodes([$read->id => $read]));
        }

        return null;
    }
}
