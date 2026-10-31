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

use function preg_match;

/**
 * Follows the rows of the tables of a database client, `app\Db`, from where they are inserted
 * (`$db->insert('table', $row)`) to where a query reads them (`$db->query('SELECT ... FROM table')`), through the
 * keyed store `app\Db::$tables`: a query names the table it reads in its SQL, which an annotation can't express.
 */
final class TaintSqlTables implements AfterExpressionAnalysisInterface
{
    private const STORE = 'app\Db::$tables';

    #[Override]
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        $call = $event->getExpr();
        if (!$call instanceof MethodCall
            || !$call->name instanceof Identifier
            || $call->isPartialFunctionApplication()
        ) {
            return null;
        }

        $source = $event->getStatementsSource();
        $node_types = $source->getNodeTypeProvider();
        $receiver_type = $node_types->getType($call->var);
        $receiver = $receiver_type !== null && $receiver_type->isSingle() ? $receiver_type->getSingleAtomic() : null;
        if (!$receiver instanceof TNamedObject || $receiver->value !== 'app\Db') {
            return null;
        }

        $codebase = $event->getCodebase();
        $args = $call->getArgs();
        $location = new CodeLocation($source, $call);
        $method = $call->name->toLowerString();
        if ($method === 'insert' && isset($args[0], $args[1])) {
            $codebase->addTaintStoreWrite(
                $source,
                self::STORE,
                $codebase->getTaintStoreNames($source, $args[0]->value),
                $codebase->getTaintNode($source, 'inserted row', $location),
                'arg',
                $node_types->getType($args[1]->value),
            );
        } elseif ($method === 'query' && isset($args[0])) {
            $type = $node_types->getType($call);
            if ($type === null) {
                return null;
            }

            // a query whose table the analysis can't tell may read any
            $tables = [[false, '']];
            $sql = $node_types->getType($args[0]->value);
            if ($sql !== null
                && $sql->isSingleStringLiteral()
                && preg_match('/\bFROM\s+(\w+)/i', $sql->getSingleStringLiteral()->value, $matches) === 1
                && isset($matches[1])
            ) {
                $tables = [[true, $matches[1]]];
            }

            $read = $codebase->getTaintNode($source, 'queried rows', $location);
            $codebase->addTaintStoreRead($source, self::STORE, $tables, $read);
            $node_types->setType($call, $type->addParentNodes([$read]));
        }

        return null;
    }
}
