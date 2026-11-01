<?php

declare(strict_types=1);

/**
 * @param list<string> $args
 */
function runConfig(array $args): int
{
    $libraryPlugins = !in_array('--no-library-plugins', $args, true);
    $options = parseOptions(array_values(array_diff($args, ['--no-library-plugins'])), ['corpus', 'project']);
    requireOptions($options, ['corpus', 'project']);
    $corpus = readJsonFile($options['corpus']);
    $project = $corpus['projects'][$options['project']] ?? null;
    if (!is_array($project)) {
        throw new RuntimeException("unknown project '{$options['project']}'");
    }

    echo buildConfig($project, $libraryPlugins);

    return 0;
}

function xmlAttr(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

/**
 * The project's psalm-perf.xml: corpus `psalm.*` settings over fixed attributes (errorLevel 1 default);
 * `vendor` always ignored.
 *
 * @param array<mixed> $project
 * @throws RuntimeException
 */
function buildConfig(array $project, bool $libraryPlugins = true): string
{
    $psalm = $project['psalm'] ?? null;
    if (!is_array($psalm) || !is_array($psalm['dirs'] ?? null) || $psalm['dirs'] === []) {
        throw new RuntimeException('project has no psalm.dirs');
    }

    $directories = static fn(array $dirs, string $indent): string => implode('', array_map(
        static fn(string $dir): string => "{$indent}<directory name=\"" . xmlAttr($dir) . "\"/>\n",
        $dirs,
    ));

    $attributes = [
        'errorLevel' => '1',
        'resolveFromConfigFile' => 'true',
        'findUnusedCode' => 'false',
        'findUnusedBaselineEntry' => 'false',
        'findUnusedIssueHandlerSuppression' => 'false',
        ...($psalm['attributes'] ?? []),
        ...($libraryPlugins ? [] : ['loadLibraryPlugins' => 'false']),
        'xmlns' => 'https://getpsalm.org/schema/config',
    ];
    $extensions = implode('', array_map(
        static fn(string $ext): string => '        <extension name="' . xmlAttr($ext) . "\"/>\n",
        $psalm['extensions'] ?? [],
    ));

    return "<?xml version=\"1.0\"?>\n<psalm\n"
        . implode('', array_map(
            static fn(string $name, mixed $value): string => "    {$name}=\"" . xmlAttr((string) $value) . "\"\n",
            array_keys($attributes),
            $attributes,
        ))
        . ">\n"
        . "    <projectFiles>\n"
        . $directories($psalm['dirs'], '        ')
        . "        <ignoreFiles allowMissingFiles=\"true\">\n"
        . $directories([...($psalm['ignore'] ?? []), 'vendor'], '            ')
        . "        </ignoreFiles>\n"
        . "    </projectFiles>\n"
        . ($extensions === '' ? '' : "    <fileExtensions>\n{$extensions}    </fileExtensions>\n")
        . "</psalm>\n";
}
