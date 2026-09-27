<?php

declare(strict_types=1);

use PhpParser\ErrorHandler\Collecting;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Interner;
use Psalm\StrId;
use Psalm\Type;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;

/**
 * Replaces wrongly-cased class names in types with their canonical (declared) casing:
 * names are resolved case-sensitively by Psalm.
 */
final class CanonicalClassNameVisitor extends MutableTypeVisitor // phpcs:ignore PSR1.Classes.ClassDeclaration
{
    /** @var array<lowercase-string, string> lowercase class name => canonical class name */
    public static array $classes = [];

    /** @var array<string, true> class names found in types without a known canonical casing */
    public static array $unresolved = [];

    private static function canonicalize(int $name): int
    {
        $str = Interner::str($name);
        $canonical = self::$classes[strtolower($str)] ?? null;
        if ($canonical === null) {
            self::$unresolved[$str] = true;
            return $name;
        }
        return Interner::intern($canonical);
    }

    #[Override]
    protected function enterNode(TypeNode &$type): ?int
    {
        if ($type instanceof TClassConstant) {
            $canonical = self::canonicalize($type->fq_classlike_name);
            if ($canonical !== $type->fq_classlike_name) {
                $type = new TClassConstant($canonical, $type->const_name, $type->from_docblock);
            }
        } elseif ($type instanceof TClassString) {
            if ($type->as !== StrId::object) {
                $type = $type->setAs(self::canonicalize($type->as), $type->as_type);
            }
        } elseif ($type instanceof TNamedObject) {
            if (!$type->is_static) {
                $type = $type->setValue(self::canonicalize($type->value));
            }
        } elseif ($type instanceof TLiteralClassString) {
            $type = $type->setClassName(self::canonicalize($type->class_name));
        }
        return null;
    }
}

function normalizeType(string $type): string
{
    $type = Type::parseString($type === '' ? 'mixed' : $type);
    (new CanonicalClassNameVisitor())->traverse($type);
    return $type->getId(true);
}

/**
 * Sorts call map keys like before names were case-sensitive (i.e. case-insensitively), to keep diffs small.
 */
function compareCallMapKeys(string|int $a, string|int $b): int
{
    return strcasecmp((string) $a, (string) $b) ?: strcmp((string) $a, (string) $b);
}

function internalNormalizeCallMap(array|string $callMap, string|int $key = 0): array|string
{
    if (is_string($callMap)) {
        return normalizeType($callMap);
    }

    $new = [];

    $value = null;
    foreach ($callMap as $key => $value) {
        // keys keep their canonical (declared) casing: names are resolved case-sensitively
        $new[$key] = internalNormalizeCallMap($value, $key);
    }
    if (is_array($value) && $key !== 'old' && $key !== 'new') {
        uksort($new, 'compareCallMapKeys');
    }

    return $new;
}

function normalizeCallMap(array $callMap): array
{
    return internalNormalizeCallMap($callMap);
}

/**
 * @return array<string, array{byRef: bool, refMode: 'rw'|'w'|'r', variadic: bool, optional: bool, type: string}>
 */
function normalizeParameters(string $func, array $parameters): array
{

    /**
     * Parse the parameter names from the map.
     *
     * @var array<string, array{byRef: bool, refMode: 'rw'|'w'|'r', variadic: bool, optional: bool, type: string}>
     */
    $normalizedEntries = [];
    
    foreach ($parameters as $key => $entry) {
        if ($key === 0) {
            continue;
        }
        $normalizedKey = $key;
        /**
         * @var array{byRef: bool, refMode: 'rw'|'w'|'r', variadic: bool, optional: bool, type: string} $normalizedEntry
         */
        $normalizedEntry = [
            'variadic' => false,
            'byRef' => false,
            'optional' => false,
            'type' => $entry,
        ];

        do {
            if (strncmp($normalizedKey, '...', 3) === 0) {
                $normalizedEntry['variadic'] = true;
                $normalizedKey = substr($normalizedKey, 3);
                continue;
            }

            if (strncmp($normalizedKey, '&', 1) === 0) {
                $normalizedEntry['byRef'] = true;
                $normalizedKey = substr($normalizedKey, 1);
                continue;
            }
            break;
        } while (true);

        // Read the reference mode
        if ($normalizedEntry['byRef']) {
            $parts = explode(' ', $normalizedKey, 2);
            if (count($parts) === 2) {
                if (!($parts[0] === 'rw' || $parts[0] === 'w' || $parts[0] === 'r')) {
                    $normalizedEntry['refMode'] = 'rw';
                } else {
                    $normalizedEntry['refMode'] = $parts[0];
                    $normalizedKey = $parts[1];
                }
            } else {
                $normalizedEntry['refMode'] = 'rw';
            }
        }
    
        // Strip prefixes.
        if (substr($normalizedKey, -1, 1) === "=") {
            $normalizedEntry['optional'] = true;
            $normalizedKey = substr($normalizedKey, 0, -1);
        }
    
        $normalizedEntry['name'] = $normalizedKey;
        $normalizedEntries[$normalizedKey] = $normalizedEntry;
    }
    
    return $normalizedEntries;
}
    
/**
 * @param array<string|int, string> $baseParameters
 * @param array<string|int, string> $customParameters
 * @return array<string|int, string>
 */
function assertEntryParameters(string $func, array $baseParameters, array $customParameters): array
{
    if ($func === 'max' || $func === 'min') {
        return $customParameters;
    }
    $denormalized = [assertTypeValidity($baseParameters[0], $customParameters[0], "Return $func")];

    $baseParameters = normalizeParameters($func, $baseParameters);
    $customParameters = normalizeParameters($func, $customParameters);

    $customParametersByVal = array_values($customParameters);

    $final = [];
    $idx = 0;
    foreach ($baseParameters as $name => $parameter) {
        if (isset($customParameters[$name])) {
            $final[$name] = assertParameter($func, $name, $customParameters[$name], $parameter);
        } elseif (isset($customParametersByVal[$idx])) {
            $final[$name] = assertParameter($func, $name, $customParametersByVal[$idx], $parameter);
        } else {
            $final[$name] = $parameter;
        }
        $idx++;
    }

    foreach ($final as $key => $param) {
        if ($key === 0) {
            continue;
        }
        if (($param['refMode'] ?? 'rw') !== 'rw') {
            $key = "{$param['refMode']} $key";
        }
        if ($param['variadic']) {
            $key = "...$key";
        }
        if ($param['byRef']) {
            $key = "&$key";
        }
        if ($param['optional']) {
            $key = "$key=";
        }
        $denormalized[$key] = $param['type'];
    }

    return $denormalized;
}
    
/**
 * @param array{
 *      byRef: bool,
 *      name?: string,
 *      refMode: 'rw'|'w'|'r',
 *      variadic: bool,
 *      optional: bool,
 *      type: string
 * } $custom
 * @param array{
 *      byRef: bool,
 *      name?: string,
 *      refMode: 'rw'|'w'|'r',
 *      variadic: bool,
 *      optional: bool,
 *      type: string
 * } $base
 */
function assertParameter(string $func, string $paramName, array $custom, array $base): array
{
    if ($func !== 'version_compare') {
        $custom['optional'] = $base['optional'];
    }
    $custom['variadic'] = $base['variadic'];
    $custom['byRef'] = $base['byRef'];
    
    $custom['type'] = assertTypeValidity($base['type'], $custom['type'], "Param $func '{$paramName}'");

    return $custom;
}

function assertTypeValidity(string $base, string $custom, string $msgPrefix): string
{
    $expectedType = Type::parseString($base);
    $callMapType = Type::parseString($custom === '' ? $base : $custom);
    
    $codebase = ProjectAnalyzer::getInstance()->getCodebase();
    try {
        if (!UnionTypeComparator::isContainedBy(
            $codebase,
            $callMapType,
            $expectedType,
            false,
            false,
            null,
            false,
            false,
        ) && !str_contains($custom, 'static')) {
            $custom = $expectedType->getId(true);
            $callMapType = $expectedType;
        }
    } catch (Throwable) {
    }
    
    if ($expectedType->hasMixed()) {
        return $custom;
    }
    $callMapType = $callMapType->getBuilder();
    if ($expectedType->isNullable() !== $callMapType->isNullable()) {
        if ($expectedType->isNullable()) {
            $callMapType->addType(new TNull());
        } else {
            $callMapType->removeType('null');
        }
    }
    return $callMapType->getId(true);
}

function writeCallMap(string $file, array $callMap): void
{
    file_put_contents($file, '<?php // phpcs:ignoreFile

return '.var_export($callMap, true).';');
}

/**
 * @template K as array-key
 * @template V
 * @param array<K, V> $a
 * @param array<K, V> $b
 * @return array<K, V>
 */
function get_changed_functions(array $a, array $b): array
{
    $changed_functions = [];

    foreach (array_intersect_key($a, $b) as $function_name => $a_data) {
        if (json_encode($b[$function_name]) !== json_encode($a_data)) {
            $changed_functions[$function_name] = $b[$function_name];
        }
    }

    return $changed_functions;
}

function extractClassesFromStatements(array $statements): array
{
    $classes = [];
    foreach ($statements as $statement) {
        if ($statement instanceof Class_) {
            $classes[strtolower($statement->namespacedName->toString())] = true;
        }
        if ($statement instanceof Namespace_) {
            $classes += extractClassesFromStatements($statement->stmts);
        }
    }

    return $classes;
}


/**
 * Collects the canonical (declared) names of the classlikes, functions and methods declared in Psalm's stubs.
 *
 * @return array{0: array<lowercase-string, string>, 1: array<lowercase-string, string>}
 *      lowercase function/method name => canonical name, lowercase class name => canonical name
 */
function collectStubNames(): array
{
    $parser = (new ParserFactory)->createForNewestSupportedVersion();
    $traverser = new NodeTraverser();
    $traverser->addVisitor(new NameResolver);

    $callables = [];
    $classes = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
        __DIR__ . '/../../stubs',
        FilesystemIterator::SKIP_DOTS,
    ));
    $paths = [];
    foreach ($files as $file) {
        $paths[] = (string) $file;
    }
    sort($paths);
    foreach ($paths as $path) {
        // some stubs contain invalid code (e.g. unquoted versions): collect errors, use the partial AST
        $stmts = $traverser->traverse($parser->parse(file_get_contents($path), new Collecting()) ?? []);
        $collect = static function (array $stmts) use (&$collect, &$callables, &$classes): void {
            foreach ($stmts as $stmt) {
                if ($stmt instanceof Namespace_) {
                    $collect($stmt->stmts);
                } elseif ($stmt instanceof Function_) {
                    $name = $stmt->namespacedName->toString();
                    $callables[strtolower($name)] ??= $name;
                } elseif ($stmt instanceof ClassLike && $stmt->namespacedName !== null) {
                    $class = $stmt->namespacedName->toString();
                    $classes[strtolower($class)] ??= $class;
                    foreach ($stmt->stmts as $class_stmt) {
                        if ($class_stmt instanceof ClassMethod) {
                            $name = $class . '::' . $class_stmt->name->toString();
                            $callables[strtolower($name)] ??= $name;
                        }
                    }
                }
            }
        };
        $collect($stmts);
    }

    return [$callables, $classes];
}

/**
 * Builds the maps used to resolve the canonical (declared) casing of names, from (in order of precedence)
 * the autogenerated callmaps (the newest version wins), Psalm's stubs and the internal symbols of the running PHP.
 *
 * @param array<int|string, array<string, mixed>> $baseMaps autogenerated callmaps, by version
 * @return array{0: array<lowercase-string, string>, 1: array<lowercase-string, string>}
 *      lowercase function/method name => canonical name, lowercase class name => canonical name
 */
function buildCanonicalNameMaps(array $baseMaps): array
{
    ksort($baseMaps);
    $callables = [];
    $classes = [];
    foreach ($baseMaps as $baseMap) {
        foreach ($baseMap as $name => $_) {
            $name = (string) $name;
            $callables[strtolower($name)] = $name;
            if (str_contains($name, '::')) {
                $class = explode('::', $name, 2)[0];
                $classes[strtolower($class)] = $class;
            }
        }
    }

    [$stub_callables, $stub_classes] = collectStubNames();
    $callables += $stub_callables;
    $classes += $stub_classes;

    foreach ([...get_declared_classes(), ...get_declared_interfaces(), ...get_declared_traits()] as $class) {
        $refl = new ReflectionClass($class);
        if (!$refl->isInternal()) {
            continue;
        }
        $class = $refl->getName();
        $classes[strtolower($class)] ??= $class;
        foreach ($refl->getMethods() as $method) {
            $name = $class . '::' . $method->getName();
            $callables[strtolower($name)] ??= $name;
        }
    }
    foreach (get_defined_functions()['internal'] as $function) {
        $name = (new ReflectionFunction($function))->getName();
        $callables[strtolower($name)] ??= $name;
    }

    return [$callables, $classes];
}

/**
 * Parses the markers of a legacy callmap parameter key (`&`, `...`, `&w `/`&r `/`&rw `, trailing `=`).
 *
 * @return array{string, int} parameter name, InternalCallMapHandler::PARAM_* flags
 */
function parseCallMapParamKey(string $func, string $key): array
{
    $flags = 0;
    $name = $key;
    do {
        if (str_starts_with($name, '...')) {
            $flags |= InternalCallMapHandler::PARAM_VARIADIC;
            $name = substr($name, 3);
            continue;
        }
        if (str_starts_with($name, '&')) {
            $flags |= InternalCallMapHandler::PARAM_BY_REF;
            $name = substr($name, 1);
            continue;
        }
        break;
    } while (true);

    if ($flags & InternalCallMapHandler::PARAM_BY_REF) {
        $parts = explode(' ', $name, 2);
        if (count($parts) === 2) {
            if ($parts[0] === 'w') {
                $flags |= InternalCallMapHandler::PARAM_REF_WRITE;
            } elseif ($parts[0] === 'r') {
                $flags |= InternalCallMapHandler::PARAM_REF_READ;
            } elseif ($parts[0] !== 'rw') {
                throw new UnexpectedValueException("Invalid reference mode in $func: $key");
            }
            $name = $parts[1];
        }
    }

    if (str_ends_with($name, '=')) {
        $flags |= InternalCallMapHandler::PARAM_OPTIONAL;
        $name = substr($name, 0, -1);
    }

    if ($name === '' || !preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $name)) {
        throw new UnexpectedValueException("Invalid parameter name in $func: $key");
    }

    return [$name, $flags];
}

function exportId(string $name): string
{
    $id = Interner::hash($name);
    if (str_contains($name, '*/')) {
        throw new UnexpectedValueException("Invalid name $name");
    }
    return ($id === PHP_INT_MIN ? 'PHP_INT_MIN' : (string) $id) . ' /* ' . $name . ' */';
}

/**
 * Writes a final (runtime) callmap, see {@see InternalCallMapHandler} for the format.
 *
 * @param array<string, array<int|string, string>> $callMap legacy (intermediate) callmap format
 * @return list<string> the names used in the callmap (function, class, method and parameter names)
 */
function writeFinalCallMap(string $file, array $callMap): array
{
    $functions = [];
    $methods = [];
    foreach ($callMap as $key => $signature) {
        $key = (string) $key;
        $overload = 0;
        if (preg_match("/^(.*)'(\\d+)$/", $key, $matches)) {
            $key = $matches[1];
            $overload = (int) $matches[2];
        }
        $return = array_shift($signature);
        $params = [];
        foreach ($signature as $param_key => $type) {
            [$name, $flags] = parseCallMapParamKey($key, (string) $param_key);
            $params[] = [$name, $type, $flags];
        }
        $signature = [$return, $params];
        if (str_contains($key, '::')) {
            [$class, $method] = explode('::', $key, 2);
            $methods[$class][$method][$overload] = $signature;
        } else {
            $functions[$key][$overload] = $signature;
        }
    }

    $names = [];

    $exportSignatures = static function (array $signatures, string $indent) use (&$names): string {
        $out = '';
        foreach ($signatures as [$return, $params]) {
            $out .= $indent . '[' . var_export($return, true);
            foreach ($params as [$name, $type, $flags]) {
                $names[$name] = true;
                $out .= ', [' . exportId($name) . ', ' . var_export($type, true) . ', ' . $flags . ']';
            }
            $out .= "],\n";
        }
        return $out;
    };

    // Alternative signatures must follow the primary one without gaps: the others were never reachable.
    $contiguous = static function (array $signatures, string $name) use ($file): ?array {
        ksort($signatures);
        $result = [];
        foreach ($signatures as $idx => $signature) {
            if ($idx !== count($result)) {
                fwrite(STDERR, "Warning: ignoring unreachable signature $name'$idx in " . basename($file) . "\n");
                continue;
            }
            $result[] = $signature;
        }
        return $result ?: null;
    };
    foreach ($functions as $function => $signatures) {
        $functions[$function] = $contiguous($signatures, $function);
    }
    foreach ($methods as $class => $class_methods) {
        foreach ($class_methods as $method => $signatures) {
            $methods[$class][$method] = $contiguous($signatures, "$class::$method");
        }
        $methods[$class] = array_filter($methods[$class]);
    }
    $functions = array_filter($functions);
    $methods = array_filter($methods);

    uksort($functions, 'compareCallMapKeys');
    uksort($methods, 'compareCallMapKeys');

    $out = "<?php // phpcs:ignoreFile\n// generated by bin/stubs/gen_callmap.php, do not edit\n"
        . "// format: see Psalm\\Internal\\Codebase\\InternalCallMapHandler\nreturn [\n    'functions' => [\n";
    foreach ($functions as $function => $signatures) {
        $names[$function] = true;
        $out .= '        ' . exportId($function) . " => [\n" . $exportSignatures($signatures, '            ')
            . "        ],\n";
    }
    $out .= "    ],\n    'methods' => [\n";
    foreach ($methods as $class => $class_methods) {
        $names[$class] = true;
        uksort($class_methods, 'compareCallMapKeys');
        $out .= '        ' . exportId($class) . " => [\n";
        foreach ($class_methods as $method => $signatures) {
            $names[$method] = true;
            $out .= '            ' . exportId($method) . " => [\n"
                . $exportSignatures($signatures, '                ') . "            ],\n";
        }
        $out .= "        ],\n";
    }
    $out .= "    ],\n];\n";

    file_put_contents($file, $out);

    return array_map('strval', array_keys($names));
}

/**
 * Writes a list of names to be preloaded in the interner (see bin/generate_str_ids.php).
 *
 * @param array<string> $names
 */
function writeInternedNames(string $file, string $generator, array $names): void
{
    $names = array_values(array_unique($names));
    sort($names, SORT_STRING);
    $out = "<?php // phpcs:ignoreFile\n// generated by $generator, do not edit\n"
        . "// names used in generated dictionaries, preloaded in Psalm\\StrId by bin/generate_str_ids.php\n"
        . "return [\n";
    foreach ($names as $name) {
        $out .= '    ' . var_export($name, true) . ",\n";
    }
    $out .= "];\n";
    file_put_contents($file, $out);
}

/**
 * Writes the final (runtime) property map, dictionaries/PropertyMap.php:
 * `[class name id => [property name id => type]]`, ids being interned ids (see {@see Interner::hash()}) of the
 * canonical (declared, case-sensitive) names; also writes the names list for bin/generate_str_ids.php.
 *
 * @param array<string, array<string, string>> $classes class name => property name => type
 */
function writePropertyMap(string $file, array $classes): void
{
    uksort($classes, 'compareCallMapKeys');
    $names = [];
    $out = "<?php // phpcs:ignoreFile\n"
        . "// generated by bin/stubs/update-property-map.php, do not edit: adapt dictionaries/ManualPropertyMap.php\n"
        . "// format: see Psalm\\Internal\\Codebase\\PropertyMap\n"
        . "return [\n";
    foreach ($classes as $class => $properties) {
        $names[] = $class;
        uksort($properties, 'compareCallMapKeys');
        $out .= '    ' . exportId($class) . " => [\n";
        foreach ($properties as $property => $type) {
            $names[] = $property;
            $out .= '        ' . exportId($property) . ' => '
                . var_export(canonicalizeTypeClassNames($type), true) . ",\n";
        }
        $out .= "    ],\n";
    }
    $out .= "];\n";
    file_put_contents($file, $out);

    writeInternedNames(
        dirname($file) . '/interned_names/PropertyMap.php',
        'bin/stubs/update-property-map.php',
        $names,
    );
}

/**
 * Canonicalizes the casing of the class names in a type, leaving the type string untouched otherwise.
 */
function canonicalizeTypeClassNames(string $type): string
{
    $parsed = Type::parseString($type);
    $canonical = $parsed;
    (new CanonicalClassNameVisitor())->traverse($canonical);
    return $canonical === $parsed ? $type : $canonical->getId(true);
}

/**
 * @param array<string, mixed> $map
 * @return array<string, mixed>
 */
function canonicalizeKeys(array $map, array $canonicalCallables): array
{
    $new = [];
    foreach ($map as $key => $value) {
        $new[$canonicalCallables[strtolower($key)] ?? $key] = $value;
    }
    return $new;
}

/**
 * Hand-written maps must use the canonical casing: report keys that don't.
 *
 * @param array<string, mixed> $map
 */
function checkKeyCasing(string $file, array $map, array $canonicalCallables): void
{
    foreach ($map as $key => $_) {
        $canonical = $canonicalCallables[strtolower(explode("'", $key, 2)[0])] ?? null;
        if ($canonical !== null && $canonical !== explode("'", $key, 2)[0]) {
            fwrite(STDERR, "Warning: $file: $key should be cased as $canonical\n");
        }
    }
}
