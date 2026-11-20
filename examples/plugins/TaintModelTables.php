<?php

declare(strict_types=1);

namespace Psalm\Example\Plugin;

use Override;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use Psalm\Codebase;
use Psalm\Plugin\EventHandler\AfterFunctionLikeAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterFunctionLikeAnalysisEvent;
use Psalm\Storage\MethodStorage;

use function count;
use function substr;

/**
 * The objects of the classes extending `app\Model` are rows of the table their `getTableName()` returns: what is set
 * on one of their fields (an `@property`) is stored in the column of that name, and what the queries store in that
 * column is what the field holds. Psalm reads the queries run with PDO and the other database layers it knows
 * (see the `@psalm-database-*` annotations).
 */
final class TaintModelTables implements AfterFunctionLikeAnalysisInterface
{
    #[Override]
    public static function afterStatementAnalysis(AfterFunctionLikeAnalysisEvent $event): ?bool
    {
        $codebase = $event->getCodebase();
        $source = $event->getStatementsSource();
        $method = $event->getStmt();
        $storage = $event->getFunctionlikeStorage();
        if (!$method instanceof ClassMethod
            || $method->name->toLowerString() !== 'gettablename'
            || !$storage instanceof MethodStorage
            || $storage->defining_fqcln === null
            || !$codebase->classExtends($storage->defining_fqcln, 'app\Model')
        ) {
            return null;
        }

        $statements = $method->stmts ?? [];
        if (count($statements) !== 1
            || !$statements[0] instanceof Return_
            || !$statements[0]->expr instanceof String_
        ) {
            return null;
        }

        $table = $statements[0]->expr->value;
        $model = $codebase->classlike_storage_provider->get($storage->defining_fqcln);
        foreach ($model->pseudo_property_get_types as $property => $_) {
            $field = substr($property, 1);
            $node = Codebase::getTaintPropertyNode($model->name . '::$' . $field);
            $codebase->addTaintDatabaseWrite($source, $node, $table, $field);
            $codebase->addTaintDatabaseRead($source, $table, $field, $node);
        }

        return null;
    }
}
