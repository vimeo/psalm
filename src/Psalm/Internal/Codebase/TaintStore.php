<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Scalar\MagicConst\Class_;
use PhpParser\Node\Scalar\MagicConst\Function_;
use PhpParser\Node\Scalar\MagicConst\Method;
use Psalm\Aliases;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\PhpVisitor\Reflector\StringStartScanner;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

use function array_values;
use function count;
use function in_array;
use function preg_match;
use function preg_split;
use function str_ends_with;
use function strlen;
use function strpos;
use function strtolower;
use function substr;
use function trim;

/**
 * Keyed stores: what a call stores under a key (`@psalm-flow ($value) -> Store::$data[$key]`) flows into what the
 * reads of every key it can be return (`@psalm-flow Store::$data[$key] -> return`), wherever the code reading it is.
 * A store is named by a class and a property, which need not exist.
 *
 * The analysis may only know the start of a key (`'lock_' . $id`), or nothing of it: each name is exact (true) or only
 * known to start with what is given (false). Two names can be the same exactly when what is known of one starts with
 * what is known of the other, and each such write and read meet in one of these nodes:
 * - an exact write of X stores into E(X), and into U(p) ("stored under p") for each start p of X;
 * - a write of a name starting with Q stores into P(Q), and into U(p) for each start p of Q;
 * - an exact read of X returns E(X), and P(p) for each start p of X;
 * - a read of a name starting with R returns U(R), and P(p) for each start p of R shorter than R.
 *
 * @psalm-type Name = array{bool, string}
 * @internal
 */
final class TaintStore
{
    private const STORE_REGEX = '/^\\\\?([A-Za-z_\\\\][\w\\\\]*)::\$(\w+)\[(\*|\$\w+(?:\[\*\])?)\](?:\[[^\]]*\])*$/';

    /**
     * Records a flow into a keyed store (`($value, $other) -> Store::$data[$key]`) or out of it
     * (`Store::$data[$key] -> return`), given as the two sides of a `@psalm-flow`. The key is a parameter, `*` for any
     * key, or `$keys[*]` for each key in a list. Only the first key of a nested store (`Store::$data[$ns][$key]`) tells
     * the values apart.
     *
     * @param ?Aliases $aliases resolve the class of the store, else it is fully qualified
     * @param ?string $self_class the class `self` and `static` name
     * @return bool whether the flow is one of a keyed store
     */
    public static function addFlow(
        FunctionLikeStorage $storage,
        string $source,
        string $target,
        string $path_type,
        ?Aliases $aliases = null,
        ?string $self_class = null,
    ): bool {
        $is_write = (bool) preg_match(self::STORE_REGEX, $target, $store_matches);
        if (!$is_write && ($target !== 'return' || !preg_match(self::STORE_REGEX, $source, $store_matches))) {
            return false;
        }

        $class_name = $store_matches[1];
        if ($self_class !== null && in_array(strtolower($class_name), ['self', 'static'], true)) {
            $class_name = $self_class;
        } elseif ($aliases !== null) {
            $class_name = Type::getFQCLNFromString($class_name, $aliases);
        }

        $store = $class_name . '::$' . $store_matches[2];

        $key = null;
        $is_list = false;
        if ($store_matches[3] !== '*') {
            $is_list = str_ends_with($store_matches[3], '[*]');
            $key = self::getParamOffset($storage, substr($store_matches[3], 1, $is_list ? -3 : null));
            if ($key === null) {
                return true;
            }
        }

        if (!$is_write) {
            $storage->taint_store_reads[] = [
                'store' => $store,
                'key' => $key,
                'is_list' => $is_list,
                'path_type' => $path_type,
            ];

            return true;
        }

        if ($source === '' || $source[0] !== '(' || !str_ends_with($source, ')')) {
            return true;
        }

        $params = [];
        foreach (preg_split('/, ?/', substr($source, 1, -1)) ?: [] as $source_param) {
            $offset = self::getParamOffset($storage, substr(trim($source_param), 1));
            if ($offset !== null) {
                $params[] = $offset;
            }
        }

        if ($params !== []) {
            $storage->taint_store_writes[] = [
                'store' => $store,
                'key' => $key,
                'is_list' => $is_list,
                'params' => $params,
                'path_type' => $path_type,
            ];
        }

        return true;
    }

    /**
     * Connects what the call stores to the store, and returns $return_type holding what the call reads from it
     *
     * @param array<array-key, Arg> $args
     */
    public static function taintCall(
        StatementsAnalyzer $statements_analyzer,
        TaintFlowGraph $graph,
        FunctionLikeStorage $storage,
        array $args,
        CodeLocation $call_location,
        Union $return_type,
    ): Union {
        if ($storage->taint_store_writes === [] && $storage->taint_store_reads === []) {
            return $return_type;
        }

        $args = self::getArgsByParam($storage, $args);
        if ($args === null) {
            // an unpacked or unknown named argument: which parameters they are given to is unknown
            return $return_type;
        }

        foreach ($storage->taint_store_writes as $write) {
            $write_node = DataFlowNode::getForAssignment(
                'store write ' . $write['store'],
                $call_location,
            );

            $has_value = false;
            foreach ($write['params'] as $offset) {
                foreach ($args[$offset] ?? [] as $arg) {
                    $arg_type = $statements_analyzer->node_data->getType($arg->value);
                    foreach ($arg_type->parent_nodes ?? [] as $parent_node) {
                        $graph->addPath($parent_node, $write_node, $write['path_type']);
                        $has_value = true;
                    }
                }
            }

            if (!$has_value) {
                continue;
            }

            $graph->addNode($write_node);
            self::addWrite(
                $graph,
                $write['store'],
                self::getKeyNames($statements_analyzer, $write['key'], $write['is_list'], $args),
                $write_node,
                $write['path_type'],
            );
        }

        foreach ($storage->taint_store_reads as $read) {
            $read_node = DataFlowNode::getForAssignment(
                'store read ' . $read['store'],
                $call_location,
            );
            $graph->addNode($read_node);
            self::addRead(
                $graph,
                $read['store'],
                self::getKeyNames($statements_analyzer, $read['key'], $read['is_list'], $args),
                $read_node,
                $read['path_type'],
            );

            $return_type = $return_type->addParentNodes([$read_node->id => $read_node]);
        }

        return $return_type;
    }

    /**
     * @param list<Name> $names
     */
    public static function addWrite(
        TaintFlowGraph $graph,
        string $store,
        array $names,
        DataFlowNode $write,
        string $path_type,
    ): void {
        foreach ($names as [$is_exact, $known]) {
            $graph->addPath($write, self::getNode($graph, $store, $is_exact ? 'E' : 'P', $known), $path_type);

            for ($length = 0; $length <= strlen($known); $length++) {
                $graph->addPath($write, self::getNode($graph, $store, 'U', substr($known, 0, $length)), $path_type);
            }
        }
    }

    /**
     * @param list<Name> $names
     */
    public static function addRead(
        TaintFlowGraph $graph,
        string $store,
        array $names,
        DataFlowNode $read,
        string $path_type,
    ): void {
        foreach ($names as [$is_exact, $known]) {
            $graph->addPath(self::getNode($graph, $store, $is_exact ? 'E' : 'U', $known), $read, $path_type);

            $start_count = $is_exact ? strlen($known) + 1 : strlen($known);
            for ($length = 0; $length < $start_count; $length++) {
                $graph->addPath(self::getNode($graph, $store, 'P', substr($known, 0, $length)), $read, $path_type);
            }
        }
    }

    /**
     * The names the expression can be: exact for a type of literals or class names, else the start the analysis knows
     *
     * @return list<Name>
     */
    public static function getNames(StatementsAnalyzer $statements_analyzer, ?Expr $expr): array
    {
        if ($expr === null) {
            return [[false, '']];
        }

        $type = $statements_analyzer->node_data->getType($expr);
        $names = $type === null ? null : self::getExactNames($statements_analyzer->getCodebase(), $type);

        return $names ?? [[false, self::getKnownStart($statements_analyzer, $expr) ?? '']];
    }

    /**
     * Records the start of the string assigned to the variable whose node is $var_node, for the reads it reaches
     */
    public static function recordAssignment(
        StatementsAnalyzer $statements_analyzer,
        TaintFlowGraph $graph,
        Expr $assigned,
        DataFlowNode $var_node,
    ): void {
        $type = $statements_analyzer->node_data->getType($assigned);
        if ($type === null || !$type->hasString() || $type->isSingleStringLiteral()) {
            return;
        }

        $start = self::getKnownStart($statements_analyzer, $assigned);
        if ($start !== null && $start !== '') {
            $graph->addStringStart($var_node, $start);
        }
    }

    /**
     * The start of the string the expression is, as far as the analysis knows it
     */
    public static function getKnownStart(StatementsAnalyzer $statements_analyzer, Expr $expr): ?string
    {
        $type = $statements_analyzer->node_data->getType($expr);
        if ($type !== null && $type->isSingleStringLiteral()) {
            return $type->getSingleStringLiteral()->value;
        }

        // what all the assignments reaching the variable start with
        if ($expr instanceof Variable && $type !== null && $type->parent_nodes !== []) {
            return self::getStartOfNodes($statements_analyzer, $type->parent_nodes);
        }

        if ($expr instanceof Concat) {
            $left_start = self::getKnownStart($statements_analyzer, $expr->left);
            if ($left_start !== null && self::isSingleLiteral($statements_analyzer, $expr->left)) {
                return $left_start . (self::getKnownStart($statements_analyzer, $expr->right) ?? '');
            }

            return $left_start;
        }

        if ($expr instanceof Class_) {
            return $statements_analyzer->getFQCLN();
        }

        // the analysis knows the names of the methods and functions: one it doesn't know is a closure's
        if ($expr instanceof Method || $expr instanceof Function_) {
            return '{closure';
        }

        // the parts up to the first the analysis doesn't know all of, then what it knows of that one
        if ($expr instanceof InterpolatedString) {
            $start = '';
            foreach ($expr->parts as $part) {
                if ($part instanceof InterpolatedStringPart) {
                    $start .= $part->value;
                    continue;
                }

                $part_start = self::getKnownStart($statements_analyzer, $part);
                if ($part_start === null || !self::isSingleLiteral($statements_analyzer, $part)) {
                    return $start . ($part_start ?? '');
                }

                $start .= $part_start;
            }

            return $start;
        }

        if ($expr instanceof FuncCall
            && $expr->name instanceof Name
            && !$expr->isFirstClassCallable()
            && $expr->name->toLowerString() === 'sprintf'
        ) {
            return self::getKnownStartOfSprintf($statements_analyzer, $expr->getArgs());
        }

        // a list of names: what they all start with
        if ($expr instanceof Array_ && $expr->items !== []) {
            $start = null;
            foreach ($expr->items as $item) {
                $item_start = $item->unpack ? null : self::getKnownStart($statements_analyzer, $item->value);
                if ($item_start === null) {
                    return null;
                }

                $start = $start === null ? $item_start : StringStartScanner::getCommonStart($start, $item_start);
            }

            return $start;
        }

        return self::getKnownStartOfMember($statements_analyzer, $expr);
    }

    /**
     * The arguments given to each parameter, null when that can't be told
     *
     * @param array<array-key, Arg> $args
     * @return array<int, list<Arg>>|null
     */
    private static function getArgsByParam(FunctionLikeStorage $storage, array $args): ?array
    {
        $by_param = [];
        $param_count = count($storage->params);

        foreach (array_values($args) as $index => $arg) {
            if ($arg->unpack) {
                return null;
            }

            $offset = $index;
            if ($arg->name !== null) {
                $offset = null;
                foreach ($storage->params as $i => $param) {
                    if ($param->name === $arg->name->name) {
                        $offset = $i;
                    }
                }

                if ($offset === null) {
                    return null;
                }
            } elseif ($param_count !== 0 && $index >= $param_count && $storage->params[$param_count - 1]->is_variadic) {
                $offset = $param_count - 1;
            }

            $by_param[$offset][] = $arg;
        }

        return $by_param;
    }

    /**
     * @psalm-mutation-free
     */
    private static function getParamOffset(FunctionLikeStorage $storage, string $param_name): ?int
    {
        foreach ($storage->params as $i => $param_storage) {
            if ($param_storage->name === $param_name) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param array<int, list<Arg>> $args
     * @return list<Name>
     */
    private static function getKeyNames(
        StatementsAnalyzer $statements_analyzer,
        ?int $key,
        bool $is_list,
        array $args,
    ): array {
        if ($key === null) {
            return [[false, '']];
        }

        $key_expr = isset($args[$key][0]) ? $args[$key][0]->value : null;
        if (!$is_list || $key_expr === null) {
            return self::getNames($statements_analyzer, $key_expr);
        }

        // a list of keys: those of its items, else those its type allows
        if ($key_expr instanceof Array_) {
            $names = [];
            foreach ($key_expr->items as $item) {
                if ($item->unpack) {
                    return [[false, '']];
                }

                foreach (self::getNames($statements_analyzer, $item->value) as $name) {
                    $names[] = $name;
                }
            }

            return $names;
        }

        $type = $statements_analyzer->node_data->getType($key_expr);
        $names = null;
        foreach ($type?->getAtomicTypes() ?? [] as $atomic) {
            if ($atomic instanceof TKeyedArray || $atomic instanceof TArray) {
                $value_type = $atomic instanceof TKeyedArray
                    ? $atomic->getGenericValueType()
                    : $atomic->type_params[1];
                $names = self::getExactNames($statements_analyzer->getCodebase(), $value_type);
            }

            if ($names === null) {
                break;
            }
        }

        return $names ?? [[false, self::getKnownStart($statements_analyzer, $key_expr) ?? '']];
    }

    private static function isSingleLiteral(StatementsAnalyzer $statements_analyzer, Expr $expr): bool
    {
        return $statements_analyzer->node_data->getType($expr)?->isSingleStringLiteral() ?? false;
    }

    /**
     * What all the assignments (and parameters) the nodes are start with
     *
     * @param array<string, DataFlowNode> $nodes
     */
    private static function getStartOfNodes(StatementsAnalyzer $statements_analyzer, array $nodes): ?string
    {
        $graph = $statements_analyzer->taint_flow_graph;
        $start = null;

        foreach ($nodes as $node_id => $_) {
            $node_start = $graph?->getStringStart($node_id)
                ?? self::getParameterStart($statements_analyzer, $node_id);
            if ($node_start === null) {
                return null;
            }

            $start = $start === null ? $node_start : StringStartScanner::getCommonStart($start, $node_start);
        }

        return $start;
    }

    /**
     * What the arguments of the parameter of the method analyzed whose node it is start with, as the scan recorded
     * them (see StringStartScanner), also in its closures
     */
    private static function getParameterStart(StatementsAnalyzer $statements_analyzer, string $node_id): ?string
    {
        $method = $statements_analyzer->getSource();
        for ($depth = 0; $depth < 8 && !$method instanceof MethodAnalyzer; $depth++) {
            $parent = $method->getSource();
            if ($parent === $method) {
                return null;
            }

            $method = $parent;
        }

        if (!$method instanceof MethodAnalyzer) {
            return null;
        }

        foreach ($method->getFunctionLikeStorage()->params as $offset => $param) {
            if ($param->location !== null
                && DataFlowNode::getForAssignment('$' . $param->name, $param->location)->id === $node_id
            ) {
                $method_id = $method->getMethodId();

                return StringStartScanner::getParameterStart(
                    $statements_analyzer->getCodebase(),
                    $method_id->fq_class_name,
                    $method_id->method_name,
                    $offset,
                );
            }
        }

        return null;
    }

    /**
     * What the property of $this or the method of the class called returns starts with, as the scan recorded it (see
     * StringStartScanner)
     */
    private static function getKnownStartOfMember(StatementsAnalyzer $statements_analyzer, Expr $expr): ?string
    {
        $codebase = $statements_analyzer->getCodebase();
        $self = $statements_analyzer->getFQCLN();
        if ($self === null) {
            return null;
        }

        if ($expr instanceof PropertyFetch
            && $expr->var instanceof Variable
            && $expr->var->name === 'this'
            && $expr->name instanceof Identifier
        ) {
            $declaring_class = $codebase->properties->getDeclaringClassForProperty(
                $self . '::$' . $expr->name->name,
                true,
            );

            return $declaring_class === null
                ? null
                : StringStartScanner::getPropertyStart($codebase, $declaring_class, $expr->name->name);
        }

        $class = null;
        $method_name = null;
        if ($expr instanceof StaticCall && $expr->class instanceof Name && $expr->name instanceof Identifier) {
            $class = in_array($expr->class->toLowerString(), ['self', 'static'], true)
                ? $self
                : ClassLikeAnalyzer::getFQCLNFromNameObject($expr->class, $statements_analyzer->getAliases());
            $method_name = $expr->name->toLowerString();
        } elseif ($expr instanceof MethodCall
            && $expr->var instanceof Variable
            && $expr->var->name === 'this'
            && $expr->name instanceof Identifier
        ) {
            $class = $self;
            $method_name = $expr->name->toLowerString();
        }

        if ($class === null || $method_name === null || !$codebase->classOrInterfaceExists($class)) {
            return null;
        }

        $declaring_method_id = $codebase->methods->getDeclaringMethodId(new MethodIdentifier($class, $method_name));

        return $declaring_method_id === null
            ? null
            : StringStartScanner::getMethodStart(
                $codebase,
                $declaring_method_id->fq_class_name,
                $declaring_method_id->method_name,
            );
    }

    /**
     * The format up to its conversions, with what they put in: only `%s` puts its argument as it is
     *
     * @param array<array-key, Arg> $args
     */
    private static function getKnownStartOfSprintf(StatementsAnalyzer $statements_analyzer, array $args): ?string
    {
        $args = array_values($args);
        if (!isset($args[0]) || !self::isSingleLiteral($statements_analyzer, $args[0]->value)) {
            return null;
        }

        $format = (string) self::getKnownStart($statements_analyzer, $args[0]->value);
        $start = '';
        for ($argument = 1;; $argument++) {
            $conversion = strpos($format, '%');
            if ($conversion === false) {
                return $start . $format;
            }

            $start .= substr($format, 0, $conversion);
            if (substr($format, $conversion, 2) !== '%s' || !isset($args[$argument])) {
                return $start;
            }

            $argument_start = self::getKnownStart($statements_analyzer, $args[$argument]->value);
            if ($argument_start === null || !self::isSingleLiteral($statements_analyzer, $args[$argument]->value)) {
                return $start . ($argument_start ?? '');
            }

            $start .= $argument_start;
            $format = substr($format, $conversion + 2);
        }
    }

    /**
     * The exact names a type of literals or class names can be, null if it can be any other string
     *
     * @return non-empty-list<array{true, string}>|null
     */
    private static function getExactNames(Codebase $codebase, Union $type): ?array
    {
        $names = [];
        foreach ($type->getAtomicTypes() as $atomic) {
            if ($atomic instanceof TLiteralClassString) {
                $atomic_names = self::getClassAndDescendants($codebase, $atomic->value, $atomic->definite_class);
            } elseif ($atomic instanceof TClassString && $atomic->as_type instanceof TNamedObject) {
                $atomic_names = self::getClassAndDescendants($codebase, $atomic->as_type->value, false);
            } elseif ($atomic instanceof TLiteralString) {
                $atomic_names = [$atomic->value];
            } else {
                return null;
            }

            if ($atomic_names === null) {
                return null;
            }

            foreach ($atomic_names as $name) {
                $names[] = [true, $name];
            }
        }

        return $names === [] ? null : $names;
    }

    /**
     * The name of the class and, unless it is exactly that class, those of the classes extending it
     *
     * @return list<string>|null
     */
    private static function getClassAndDescendants(Codebase $codebase, string $class, bool $is_exact): ?array
    {
        if (!$codebase->classExists($class)) {
            return $is_exact ? [$class] : null;
        }

        $storage = $codebase->classlike_storage_provider->get($class);
        $names = [$storage->name];
        if ($is_exact || $storage->final) {
            return $names;
        }

        foreach (ClassLikeStorageProvider::getAll() as $descendant) {
            if (isset($descendant->parent_classes[strtolower($storage->name)])) {
                $names[] = $descendant->name;
            }
        }

        return $names;
    }

    /**
     * @param 'E'|'U'|'P' $kind
     */
    private static function getNode(TaintFlowGraph $graph, string $store, string $kind, string $known): DataFlowNode
    {
        $node = DataFlowNode::getForPropertyFetch($store . '[' . $kind . ':' . $known . ']');
        $graph->addNode($node);

        return $node;
    }
}
