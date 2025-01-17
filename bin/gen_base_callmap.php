<?php

declare(strict_types=1);

$callmap = [];

/**
 * @var ?ReflectionType $reflection_type
 */
function typeToString($reflection_type = null): string
{
    if (!$reflection_type) {
        return 'string';
    }

    if ($reflection_type instanceof ReflectionNamedType) {
        $type = $reflection_type->getName();
    } elseif ($reflection_type instanceof ReflectionUnionType) {
        $type = implode(
            '|',
            array_map(
                static function (ReflectionNamedType $reflection): string { return $reflection->getName(); },
                $reflection_type->getTypes()
            )
        );
    } else if ($reflection_type instanceof ReflectionType) {
        $type = $reflection_type->__toString();
    } else {
        throw new LogicException('Unexpected reflection class ' . get_class($reflection_type) . ' found.');
    }

    if ($reflection_type->allowsNull()) {
        $type .= '|null';
    }

    return $type;
}

/**
 * @return array<string, array{byRef: bool, refMode: 'rw'|'w'|'r', variadic: bool, optional: bool, type: string}>
 */
function paramsToEntries(ReflectionFunctionAbstract $reflectionFunction): array
{
    $res = [typeToString($reflectionFunction->getReturnType())];

    foreach ($reflectionFunction->getParameters() as $param) {
        $key = $param->getName();
        if ($param->isPassedByReference()) {
            $key = "&$key";
        }
        if ($param->isVariadic()) {
            $key = "...$key";
        }
        if ($param->isOptional()) {
            $key .= '=';
        }

        $res[$key] = typeToString($param->getType());
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
        if ($name === 'paramstoentries') continue;
        if ($name === 'typetostring') continue;
        $func = new ReflectionFunction($name);

        $args = paramsToEntries($func);

        $callmap[$name] = $args;
    }
}

foreach (get_declared_classes() as $class) {
    $refl = new ReflectionClass($class);

    foreach ($refl->getMethods() as $method) {
        $args = paramsToEntries($method);
    
        $callmap[strtolower($class.'::'.$method->getName())] = $args;
    }
}

file_put_contents(__DIR__.'/../dictionaries/autogen/CallMap_'.PHP_MAJOR_VERSION.PHP_MINOR_VERSION.'.php', '<?php // phpcs:ignoreFile

return '.var_export($callmap, true).';');
