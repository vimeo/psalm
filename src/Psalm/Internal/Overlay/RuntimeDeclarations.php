<?php

declare(strict_types=1);

namespace Psalm\Internal\Overlay;

use JsonException;
use Psalm\Codebase;
use Psalm\Config;

use function array_diff_key;
use function array_filter;
use function array_flip;
use function array_keys;
use function array_values;
use function get_defined_constants;
use function get_defined_functions;
use function get_included_files;
use function getenv;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function strrpos;
use function strtolower;
use function substr;

use const JSON_THROW_ON_ERROR;

/**
 * The functions and constants of an overlaid file that the process defines already: Composer loaded the file as it is
 * on disk (`autoload.files`). What the scan of the file declares and the scan of its overlay no longer does must not
 * be taken from the process.
 *
 * @psalm-type Declarations = array{functions: array<string, true>, constants: array<string, true>}
 * @psalm-type Removed = array{functions: list<string>, constants: list<string>}
 * @internal
 */
final class RuntimeDeclarations
{
    /** What a fresh process forgets: the declarations the overlay pass found removed, as JSON */
    public const REMOVED_ENV = 'PSALM_OVERLAY_REMOVED';

    /**
     * What the scans of the files this process loaded declare: lowercase function ids and constant names
     *
     * @param list<string> $paths
     * @return Declarations
     */
    public static function getDeclarations(Codebase $codebase, array $paths): array
    {
        $loaded = array_flip(get_included_files());
        $declarations = ['functions' => [], 'constants' => []];

        foreach ($paths as $path) {
            if (!isset($loaded[$path]) || !$codebase->file_storage_provider->has($path)) {
                continue;
            }

            $file_storage = $codebase->file_storage_provider->get($path);
            foreach (array_keys($file_storage->functions) as $function_id) {
                $declarations['functions'][$function_id] = true;
            }
            foreach (array_keys($file_storage->constants) as $constant) {
                $declarations['constants'][$constant] = true;
            }
        }

        return $declarations;
    }

    /**
     * @param Declarations $before
     * @param Declarations $after
     * @return Removed
     * @psalm-pure
     */
    public static function getRemoved(array $before, array $after): array
    {
        return [
            'functions' => array_keys(array_diff_key($before['functions'], $after['functions'])),
            'constants' => array_keys(array_diff_key($before['constants'], $after['constants'])),
        ];
    }

    /**
     * Only what the process defines from its own code: what PHP and its extensions define stays
     *
     * @param Removed $removed
     */
    public static function forget(Config $config, array $removed): void
    {
        $user_functions = array_flip(get_defined_functions()['user']);
        $user_constants = get_defined_constants(true)['user'] ?? [];

        $config->forgetPredefined(
            array_values(array_filter(
                $removed['functions'],
                static fn(string $function): bool => isset($user_functions[$function]),
            )),
            array_values(array_filter(
                $removed['constants'],
                static fn(string $constant): bool => isset($user_constants[$constant])
                    || isset($user_constants[self::getRuntimeName($constant)]),
            )),
        );
    }

    /**
     * @param Removed $removed
     * @psalm-pure
     */
    public static function encode(array $removed): string
    {
        return json_encode($removed, JSON_THROW_ON_ERROR);
    }

    /**
     * What the process that started this one passed on
     *
     * @return Removed
     */
    public static function fromEnvironment(): array
    {
        $json = getenv(self::REMOVED_ENV);
        $removed = ['functions' => [], 'constants' => []];
        if (!is_string($json)) {
            return $removed;
        }

        try {
            /** @var mixed $data */
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $removed;
        }

        foreach (['functions', 'constants'] as $kind) {
            /** @var mixed $names */
            $names = is_array($data) ? $data[$kind] ?? [] : [];
            if (is_array($names)) {
                $removed[$kind] = array_values(array_filter($names, is_string(...)));
            }
        }

        return $removed;
    }

    /**
     * The name PHP gives a constant: its namespace in lowercase
     *
     * @psalm-pure
     */
    private static function getRuntimeName(string $constant): string
    {
        $separator = strrpos($constant, '\\');

        return $separator === false
            ? $constant
            : strtolower(substr($constant, 0, $separator)) . substr($constant, $separator);
    }
}
