<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Closure;
use InvalidArgumentException;
use Psalm\Aliases;
use Psalm\Codebase;
use Psalm\Exception\CircularReferenceException;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ConstFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\SimpleTypeInferer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\Internal\Scanner\UnresolvedConstant\ArrayOffsetFetch;
use Psalm\Internal\Scanner\UnresolvedConstant\ArraySpread;
use Psalm\Internal\Scanner\UnresolvedConstant\ArrayValue;
use Psalm\Internal\Scanner\UnresolvedConstant\ClassConstant;
use Psalm\Internal\Scanner\UnresolvedConstant\Constant;
use Psalm\Internal\Scanner\UnresolvedConstant\EnumNameFetch;
use Psalm\Internal\Scanner\UnresolvedConstant\EnumPropertyFetch;
use Psalm\Internal\Scanner\UnresolvedConstant\EnumValueFetch;
use Psalm\Internal\Scanner\UnresolvedConstant\ScalarValue;
use Psalm\Internal\Scanner\UnresolvedConstant\UnresolvedAdditionOp;
use Psalm\Internal\Scanner\UnresolvedConstant\UnresolvedBinaryOp;
use Psalm\Internal\Scanner\UnresolvedConstant\UnresolvedBitwiseAnd;
use Psalm\Internal\Scanner\UnresolvedConstant\UnresolvedBitwiseOr;
use Psalm\Internal\Scanner\UnresolvedConstant\UnresolvedBitwiseXor;
use Psalm\Internal\Scanner\UnresolvedConstant\UnresolvedConcatOp;
use Psalm\Internal\Scanner\UnresolvedConstant\UnresolvedDivisionOp;
use Psalm\Internal\Scanner\UnresolvedConstant\UnresolvedExpression;
use Psalm\Internal\Scanner\UnresolvedConstant\UnresolvedMultiplicationOp;
use Psalm\Internal\Scanner\UnresolvedConstant\UnresolvedShift;
use Psalm\Internal\Scanner\UnresolvedConstant\UnresolvedShiftLeft;
use Psalm\Internal\Scanner\UnresolvedConstant\UnresolvedSubtractionOp;
use Psalm\Internal\Scanner\UnresolvedConstant\UnresolvedTernary;
use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Internal\Type\LiteralCast;
use Psalm\Storage\FileStorage;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TEnumCase;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Union;
use ReflectionProperty;
use UnitEnum;

use function array_key_exists;
use function count;
use function ctype_digit;
use function is_array;
use function is_float;
use function is_int;
use function is_string;
use function ltrim;
use function serialize;
use function spl_object_id;
use function str_contains;
use function strtolower;

/**
 * @internal
 */
final class ConstantTypeResolver
{
    /**
     * The expressions being inferred, by object id: an expression reached again through the constants it reads is
     * circular
     *
     * @var array<int, true>
     */
    private static array $inferring_expression_ids = [];

    /**
     * The global constants being resolved, by name: a constant reached again through the constants it reads is
     * circular
     *
     * @var array<string, true>
     */
    private static array $resolving_constant_names = [];

    /**
     * The number of cycles found: a type resolved while one was cut may lack the definitions it skipped
     */
    private static int $cycles_found = 0;

    /** @var array<string, ?Union>|null */
    private static ?array $scan_time_memo = null;

    /**
     * Reuse dependencies only while evaluating one scan-time name, before storage can change.
     *
     * @param Closure(): ?Union $infer
     */
    public static function withScanTimeMemo(Closure $infer): ?Union
    {
        if (self::$scan_time_memo !== null) {
            return $infer();
        }

        self::$scan_time_memo = [];
        try {
            return $infer();
        } finally {
            self::$scan_time_memo = null;
        }
    }

    /**
     * The type of a value the scanner left unresolved, as a parameter default
     */
    public static function resolveToUnion(
        Codebase $codebase,
        UnresolvedConstantComponent $c,
        ?StatementsAnalyzer $statements_analyzer = null,
        array $visited_constant_ids = [],
    ): Union {
        if ($c instanceof UnresolvedExpression) {
            return self::inferExpression($codebase, $c, $visited_constant_ids) ?? Type::getMixed();
        }

        if ($c instanceof Constant) {
            return self::resolveGlobalConstant($codebase, self::getNamesInItsScope($c), $statements_analyzer)
                ?? Type::getMixed();
        }

        return new Union([self::resolve($codebase->classlikes, $c, $statements_analyzer, $visited_constant_ids)]);
    }

    /**
     * Replay handles the expressions the scanner can infer. Use the structural form only for unsupported shapes,
     * so a constant read during replay does not need to be resolved again by the fallback.
     */
    private static function inferExpression(
        Codebase $codebase,
        UnresolvedExpression $c,
        array $visited_constant_ids,
    ): ?Union {
        $c_id = spl_object_id($c);

        if (isset(self::$inferring_expression_ids[$c_id])) {
            self::$cycles_found++;
            throw new CircularReferenceException('Found a circular reference');
        }

        self::$inferring_expression_ids[$c_id] = true;

        try {
            $type = SimpleTypeInferer::infer(
                $codebase,
                new NodeDataProvider(),
                $c->expr,
                new Aliases($c->namespace),
                null,
                null,
                $c->fq_classlike_name,
                replay_constants: true,
            );

            if ($type !== null || $c->fallback === null) {
                return $type;
            }

            return new Union([self::resolve($codebase->classlikes, $c->fallback, null, $visited_constant_ids)]);
        } finally {
            unset(self::$inferring_expression_ids[$c_id]);
        }
    }

    /**
     * The type of the global constant the names refer to, as PHP resolves them: the first name a definition has,
     * `A\SOME` before the global `SOME`. Defined in several branches, the constant has the value of one of the
     * definitions, known or resolved later. With an analyzer, only the definitions its code sees: global ones, and
     * those of its file and of the files it includes.
     *
     * @param non-empty-list<string> $names
     */
    public static function resolveGlobalConstant(
        Codebase $codebase,
        array $names,
        ?StatementsAnalyzer $statements_analyzer = null,
    ): ?Union {
        if ($codebase->scanner->is_scanning) {
            if (self::$scan_time_memo === null) {
                return self::findGlobalConstant($codebase, $names, $statements_analyzer);
            }
            $memo =& self::$scan_time_memo;
        } else {
            $memo =& $codebase->global_constant_types;
        }

        $key = serialize([
            $names,
            $statements_analyzer?->getRootFilePath(),
            $statements_analyzer?->getFilePath(),
        ]);

        if (array_key_exists($key, $memo)) {
            return $memo[$key];
        }

        $cycles_found = self::$cycles_found;
        $type = self::findGlobalConstant($codebase, $names, $statements_analyzer);

        if ($cycles_found === self::$cycles_found) {
            $memo[$key] = $type;
        }

        return $type;
    }

    /**
     * @param non-empty-list<string> $names
     */
    private static function findGlobalConstant(
        Codebase $codebase,
        array $names,
        ?StatementsAnalyzer $statements_analyzer,
    ): ?Union {
        foreach ($names as $name) {
            if (isset(self::$resolving_constant_names[$name])) {
                self::$cycles_found++;
                throw new CircularReferenceException('Found a circular reference');
            }

            self::$resolving_constant_names[$name] = true;

            try {
                $type = self::resolveDefinitionsOf($codebase, $name, $statements_analyzer);
            } finally {
                unset(self::$resolving_constant_names[$name]);
            }

            if ($type !== null) {
                return $type;
            }
        }

        // the constants of PHP, and of the files the autoloader included
        foreach ($names as $name) {
            $type = ConstFetchAnalyzer::getGlobalConstType($codebase, $name, $name);

            if ($type !== null) {
                return $type;
            }
        }

        return null;
    }

    private static function resolveDefinitionsOf(
        Codebase $codebase,
        string $name,
        ?StatementsAnalyzer $statements_analyzer,
    ): ?Union {
        $definitions = [];

        // the registry of global constants: stubs, files the autoloader included, constants made global
        $definitions[] = $codebase->getUnresolvedGlobalConstant($name) ?? $codebase->getStubbedConstantType($name);

        $file_storages = [];

        if ($statements_analyzer) {
            foreach ([$statements_analyzer->getRootFilePath(), $statements_analyzer->getFilePath()] as $file_path) {
                $file_storage = $codebase->file_storage_provider->get($file_path);
                $file_storages[] = $file_storage;

                if (isset($file_storage->declaring_constants[$name])) {
                    $declaring_storage = $codebase->file_storage_provider->get(
                        $file_storage->declaring_constants[$name],
                    );
                    $definitions[] = $declaring_storage->unresolved_constants[$name]
                        ?? $declaring_storage->constants[$name];
                }
            }
        } elseif (!$codebase->scanner->is_scanning && $definitions === [null]) {
            // the registry has the constants of every file where they are global
            foreach (FileStorageProvider::getAll() as $file_storage) {
                if (isset($file_storage->constants[$name])) {
                    $definitions[] = $file_storage->unresolved_constants[$name] ?? $file_storage->constants[$name];
                }
            }
        }

        foreach ($codebase->getDefinitionsResolvedLater($name) as [$declaring_file_path, $value, $global]) {
            if ($global || (!$codebase->scanner->is_scanning
                && (!$file_storages || self::sees($file_storages, $declaring_file_path)))
            ) {
                $definitions[] = $value;
            }
        }

        $type = null;

        foreach ($definitions as $definition) {
            try {
                $definition_type = $definition instanceof UnresolvedConstantComponent
                    ? self::resolveToUnion($codebase, $definition, $statements_analyzer)
                    : $definition;
            } catch (CircularReferenceException) {
                // reported where the constants are declared
                continue;
            }

            if ($definition_type !== null) {
                $type = Type::combineUnionTypes($type, $definition_type);
            }
        }

        return $type;
    }

    /**
     * Whether the code of the files sees the constants of the declaring file: its own, or one it includes
     *
     * @param non-empty-list<FileStorage> $file_storages
     * @psalm-capabilities read-props
     */
    private static function sees(array $file_storages, string $declaring_file_path): bool
    {
        $declaring_file_path_lc = strtolower($declaring_file_path);

        foreach ($file_storages as $file_storage) {
            if (strtolower($file_storage->file_path) === $declaring_file_path_lc
                || isset($file_storage->required_file_paths[$declaring_file_path_lc])
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * The names the constant may have where it was written: PHP resolves an unqualified `SOME` of namespace `A` to
     * `A\SOME` if it exists, otherwise to the global `SOME`
     *
     * @return non-empty-list<string>
     * @psalm-pure
     */
    private static function getNamesInItsScope(Constant $c): array
    {
        $name = ltrim($c->name, '\\');

        if ($c->is_fully_qualified || $c->namespace === '') {
            return [$name];
        }

        return str_contains($name, '\\')
            ? [$c->namespace . '\\' . $name]
            : [$c->namespace . '\\' . $name, $name];
    }

    private static function resolveShift(UnresolvedShift $c, Atomic $left, Atomic $right): Atomic
    {
        if ($left instanceof TLiteralInt && $right instanceof TLiteralInt) {
            // a negative shift throws, as it does in ArithmeticOpAnalyzer
            if ($right->value < 0) {
                return new TNever();
            }

            return self::getLiteralTypeFromScalarValue(
                $c instanceof UnresolvedShiftLeft ? $left->value << $right->value : $left->value >> $right->value,
            );
        }

        return new TInt();
    }

    public static function resolve(
        ClassLikes $classlikes,
        UnresolvedConstantComponent $c,
        ?StatementsAnalyzer $statements_analyzer = null,
        array $visited_constant_ids = [],
    ): Atomic {
        $c_id = spl_object_id($c);

        if (isset($visited_constant_ids[$c_id])) {
            self::$cycles_found++;
            throw new CircularReferenceException('Found a circular reference');
        }

        $visited_constant_ids[$c_id] = true;

        if ($c instanceof ScalarValue) {
            return self::getLiteralTypeFromScalarValue($c->value);
        }

        if ($c instanceof UnresolvedExpression) {
            $type = self::inferExpression(
                $statements_analyzer?->getCodebase() ?? ProjectAnalyzer::getInstance()->getCodebase(),
                $c,
                $visited_constant_ids,
            );

            return $type !== null && count($type->getAtomicTypes()) === 1 ? $type->getSingleAtomic() : new TMixed;
        }

        if ($c instanceof UnresolvedBinaryOp) {
            $left = self::resolve(
                $classlikes,
                $c->left,
                $statements_analyzer,
                $visited_constant_ids,
            );
            $right = self::resolve(
                $classlikes,
                $c->right,
                $statements_analyzer,
                $visited_constant_ids,
            );

            if ($left instanceof TMixed || $right instanceof TMixed) {
                return new TMixed;
            }

            if ($c instanceof UnresolvedConcatOp) {
                if (($left instanceof TLiteralString
                        || $left instanceof TLiteralFloat
                        || $left instanceof TLiteralInt)
                    && ($right instanceof TLiteralString
                        || $right instanceof TLiteralFloat
                        || $right instanceof TLiteralInt)
                ) {
                    return Type::getAtomicStringFromLiteral(
                        LiteralCast::toString($left->value) . LiteralCast::toString($right->value),
                    );
                }

                return new TString();
            }

            if ($c instanceof UnresolvedBitwiseOr
                || $c instanceof UnresolvedBitwiseXor
                || $c instanceof UnresolvedBitwiseAnd
            ) {
                return self::resolveBitwiseOp($c, $left, $right);
            }

            if ($c instanceof UnresolvedShift) {
                return self::resolveShift($c, $left, $right);
            }

            if ($c instanceof UnresolvedAdditionOp
                || $c instanceof UnresolvedSubtractionOp
                || $c instanceof UnresolvedDivisionOp
                || $c instanceof UnresolvedMultiplicationOp
            ) {
                return self::resolveArithmeticOp($c, $left, $right);
            }

            return new TMixed;
        }

        if ($c instanceof UnresolvedTernary) {
            $cond = self::resolve(
                $classlikes,
                $c->cond,
                $statements_analyzer,
                $visited_constant_ids,
            );
            $if = $c->if ? self::resolve(
                $classlikes,
                $c->if,
                $statements_analyzer,
                $visited_constant_ids,
            ) : null;
            $else = self::resolve(
                $classlikes,
                $c->else,
                $statements_analyzer,
                $visited_constant_ids,
            );

            if ($cond instanceof TLiteralFloat
                || $cond instanceof TLiteralInt
                || $cond instanceof TLiteralString
            ) {
                if ($cond->value) {
                    return $if ?? $cond;
                }
            } elseif ($cond instanceof TFalse || $cond instanceof TNull) {
                return $else;
            } elseif ($cond instanceof TTrue) {
                return $if ?? $cond;
            }
        }

        if ($c instanceof ArrayValue) {
            $properties = [];
            $auto_key = 0;

            if (!$c->entries) {
                return new TArray([Type::getNever(), Type::getNever()]);
            }

            $is_list = true;

            foreach ($c->entries as $entry) {
                if ($entry instanceof ArraySpread) {
                    $spread_array = self::resolve(
                        $classlikes,
                        $entry->array,
                        $statements_analyzer,
                        $visited_constant_ids,
                    );

                    if ($spread_array instanceof TArray && $spread_array->isEmptyArray()) {
                        continue;
                    }

                    if (!$spread_array instanceof TKeyedArray) {
                        return Type::getArrayAtomic();
                    }

                    foreach ($spread_array->properties as $k => $spread_array_type) {
                        $properties[is_string($k) ? $k : $auto_key++] = $spread_array_type;
                    }
                    continue;
                }

                if ($entry->key) {
                    $key_type = self::resolve(
                        $classlikes,
                        $entry->key,
                        $statements_analyzer,
                        $visited_constant_ids,
                    );

                    if (!$key_type instanceof TLiteralInt
                        || $key_type->value !== $auto_key
                    ) {
                        $is_list = false;
                    }
                } else {
                    $key_type = new TLiteralInt($auto_key);
                }

                if ($key_type instanceof TLiteralInt
                    || $key_type instanceof TLiteralString
                ) {
                    $key_value = $key_type->value;
                    if ($key_type instanceof TLiteralInt) {
                        $auto_key = $key_type->value + 1;
                    } elseif (ctype_digit($key_type->value)) {
                        $auto_key = ((int) $key_type->value) + 1;
                    }
                } else {
                    return Type::getArrayAtomic();
                }

                $value_type = new Union([self::resolve(
                    $classlikes,
                    $entry->value,
                    $statements_analyzer,
                    $visited_constant_ids,
                )]);

                $properties[$key_value] = $value_type;
            }

            if (empty($properties)) {
                $resolved_type = Type::getEmptyArrayAtomic();
            } else {
                $resolved_type = TKeyedArray::make($properties, null, null, $is_list);
            }

            return $resolved_type;
        }

        if ($c instanceof ClassConstant) {
            if ($c->name === 'class') {
                return new TLiteralClassString($c->fqcln);
            }

            $found_type = $classlikes->getClassConstantType(
                $c->fqcln,
                $c->name,
                ReflectionProperty::IS_PRIVATE,
                $statements_analyzer,
                $visited_constant_ids,
            );

            if ($found_type) {
                return $found_type->getSingleAtomic();
            }
        }

        if ($c instanceof ArrayOffsetFetch) {
            $var_type = self::resolve(
                $classlikes,
                $c->array,
                $statements_analyzer,
                $visited_constant_ids,
            );

            $offset_type = self::resolve(
                $classlikes,
                $c->offset,
                $statements_analyzer,
                $visited_constant_ids,
            );

            if ($var_type instanceof TKeyedArray
                && ($offset_type instanceof TLiteralInt
                    || $offset_type instanceof TLiteralString)
            ) {
                $union = $var_type->properties[$offset_type->value] ?? null;

                if ($union && $union->isSingle()) {
                    return $union->getSingleAtomic();
                }
            }
        }

        if ($c instanceof Constant) {
            $type = self::resolveGlobalConstant(
                $statements_analyzer?->getCodebase() ?? ProjectAnalyzer::getInstance()->getCodebase(),
                self::getNamesInItsScope($c),
                $statements_analyzer,
            );

            return $type !== null && count($type->getAtomicTypes()) === 1 ? $type->getSingleAtomic() : new TMixed;
        }

        if ($c instanceof EnumPropertyFetch) {
            if ($classlikes->enumExists($c->fqcln)) {
                $enum_storage = $classlikes->getStorageFor($c->fqcln);
                if (isset($enum_storage->enum_cases[$c->case])) {
                    if ($c instanceof EnumValueFetch) {
                        $value = $enum_storage->enum_cases[$c->case]->value;

                        if ($value !== null) {
                            if ($value instanceof UnresolvedConstantComponent) {
                                return self::resolve(
                                    $classlikes,
                                    $value,
                                    $statements_analyzer,
                                    $visited_constant_ids,
                                );
                            } else {
                                return $value;
                            }
                        }
                    } elseif ($c instanceof EnumNameFetch) {
                        return Type::getString($c->case)->getSingleAtomic();
                    }
                }
            }
        }

        return new TMixed;
    }

    private static function resolveArithmeticOp(UnresolvedBinaryOp $c, Atomic $left, Atomic $right): Atomic
    {
        if (($left instanceof TLiteralFloat || $left instanceof TLiteralInt)
            && ($right instanceof TLiteralFloat || $right instanceof TLiteralInt)
        ) {
            if ($c instanceof UnresolvedAdditionOp) {
                return self::getLiteralTypeFromScalarValue($left->value + $right->value);
            }

            if ($c instanceof UnresolvedSubtractionOp) {
                return self::getLiteralTypeFromScalarValue($left->value - $right->value);
            }

            if ($c instanceof UnresolvedDivisionOp) {
                // a division by zero throws, so the constant has no value
                if ($right->value == 0) {
                    return new TNever();
                }

                return self::getLiteralTypeFromScalarValue($left->value / $right->value);
            }

            return self::getLiteralTypeFromScalarValue($left->value * $right->value);
        }

        if ($left instanceof TKeyedArray && $right instanceof TKeyedArray) {
            return TKeyedArray::make(
                $left->properties + $right->properties,
                null,
            );
        }

        return new TMixed;
    }

    /**
     * @psalm-pure
     */
    private static function resolveBitwiseOp(UnresolvedBinaryOp $c, Atomic $left, Atomic $right): Atomic
    {
        if (!($left instanceof TInt || $left instanceof TLiteralFloat)
            || !($right instanceof TInt || $right instanceof TLiteralFloat)
        ) {
            return new TMixed;
        }

        if (!($left instanceof TLiteralInt || $left instanceof TLiteralFloat)
            || !($right instanceof TLiteralInt || $right instanceof TLiteralFloat)
        ) {
            return new TInt();
        }

        $left_int = LiteralCast::toInt($left->value);
        $right_int = LiteralCast::toInt($right->value);

        if ($left_int === null || $right_int === null) {
            return new TInt();
        }

        return new TLiteralInt(match (true) {
            $c instanceof UnresolvedBitwiseOr => $left_int | $right_int,
            $c instanceof UnresolvedBitwiseXor => $left_int ^ $right_int,
            default => $left_int & $right_int,
        });
    }

    /**
     * Note: This takes an array, but any array should only contain other arrays and scalars.
     */
    public static function getLiteralTypeFromScalarValue(array|string|int|float|bool|UnitEnum|null $value): Atomic
    {
        if ($value instanceof UnitEnum) {
            return new TEnumCase($value::class, $value->name);
        }
        if (is_array($value)) {
            if (empty($value)) {
                return Type::getEmptyArrayAtomic();
            }

            $types = [];
            /** @var array|scalar|null $val */
            foreach ($value as $key => $val) {
                $types[$key] = new Union([self::getLiteralTypeFromScalarValue($val)]);
            }
            return TKeyedArray::make($types, null);
        }

        if (is_string($value)) {
            return Type::getAtomicStringFromLiteral($value);
        }

        if (is_int($value)) {
            return new TLiteralInt($value);
        }

        if (is_float($value)) {
            return new TLiteralFloat($value);
        }

        if ($value === false) {
            return new TFalse();
        }

        if ($value === true) {
            return new TTrue();
        }

        if ($value === null) {
            return new TNull();
        }

        throw new InvalidArgumentException('$value must be a scalar.');
    }
}
