<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols


declare(strict_types=1);

$callmap = [];

function namedTypeName(ReflectionNamedType $refl): string
{
    return $refl->getName();
}

/**
 * @psalm-param ?ReflectionType $reflection_type
 */
function typeToString($reflection_type, string $defaultType): string
{
    if (!$reflection_type) {
        return $defaultType;
    }

    if ($reflection_type instanceof ReflectionNamedType) {
        $type = $reflection_type->getName();
    } elseif ($reflection_type instanceof ReflectionUnionType) {
        $type = implode('|', array_map('namedTypeName', $reflection_type->getTypes()));
    } elseif ($reflection_type instanceof ReflectionType) {
        $type = $reflection_type->__toString();
    } else {
        throw new LogicException('Unexpected reflection class ' . get_class($reflection_type) . ' found.');
    }

    if ($reflection_type->allowsNull() && $type !== 'mixed') {
        $type .= '|null';
    }

    return $type;
}

/**
 * @return array<string, array{byRef: bool, refMode: 'rw'|'w'|'r', variadic: bool, optional: bool, type: string}>
 */
function paramsToEntries(ReflectionFunctionAbstract $reflectionFunction, string $defaultReturnType): array
{
    // phpcs:disable SlevomatCodingStandard.Numbers.RequireNumericLiteralSeparator.RequiredNumericLiteralSeparator
    $res = PHP_VERSION_ID >= 80100 ? (
        $reflectionFunction->getTentativeReturnType() ?? $reflectionFunction->getReturnType()
    ) : $reflectionFunction->getReturnType();

    $res = [typeToString($res, $defaultReturnType)];

    foreach ($reflectionFunction->getParameters() as $param) {
        $key = $param->getName();
        if ($param->isVariadic()) {
            $key = "...$key";
        }
        if ($param->isPassedByReference()) {
            $key = "&$key";
        }
        if ($param->isOptional()) {
            $key .= '=';
        }

        $res[$key] = typeToString($param->getType(), 'mixed');
    }

    return $res;
}

// TEMP: not recommended, install the extension in the Dockerfile, instead
foreach ([
    'couchbase/couchbase.php',
    'ibm_db2/ibm_db2.php',
] as $stub) {
    if ($stub === 'ibm_db2/ibm_db2.php' && PHP_MAJOR_VERSION < 8) {
        continue;
    }
    $stub = file_get_contents("https://github.com/JetBrains/phpstorm-stubs/raw/refs/heads/master/$stub");
    if (PHP_MAJOR_VERSION === 7 && PHP_MINOR_VERSION === 0) {
        $stub = str_replace(['?', '<php'], ['', '<?php'], $stub);
        $stub = str_replace(['public const', 'protected const', 'private const'], 'const', $stub);
    }
    file_put_contents('temp.php', $stub);
    require 'temp.php';
}

foreach (get_defined_functions() as $sub) {
    foreach ($sub as $name) {
        $name = strtolower($name);
        if ($name === 'paramstoentries') {
            continue;
        }
        if ($name === 'typetostring') {
            continue;
        }
        if ($name === 'namedtypename') {
            continue;
        }
        $func = new ReflectionFunction($name);

        $args = paramsToEntries($func, 'mixed');

        $callmap[$name] = $args;
    }
}

foreach (get_declared_classes() as $class) {
    $refl = new ReflectionClass($class);

    foreach ($refl->getMethods() as $method) {
        $args = paramsToEntries($method, $method->getName() === '__construct' ? 'void' : 'mixed');
    
        $callmap[strtolower($class.'::'.$method->getName())] = $args;
    }
}

$payload = '<?php // phpcs:ignoreFile

return '.var_export($callmap, true).';';
$f = __DIR__.'/../../dictionaries/autogen/CallMap_'.PHP_MAJOR_VERSION.PHP_MINOR_VERSION.'.php';

file_put_contents($f, $payload);
