<?php

declare(strict_types=1);

namespace Psalm\Internal\Overlay;

use InvalidArgumentException;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Internal\Codebase\ConstantTypeResolver;
use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Issue\CodeIssue;
use Psalm\Type\Atomic;
use Psalm\Type\Union;
use Throwable;

use function array_key_exists;
use function array_keys;
use function array_unique;
use function array_values;
use function is_array;
use function is_object;
use function is_scalar;
use function is_string;
use function ksort;
use function serialize;
use function strrpos;
use function strtolower;
use function substr;

/**
 * What other files can observe of a file: the storages the scanner makes of it, without positions. Comparing
 * them before and after an overlay is rescanned tells what the overlay changes for other files.
 *
 * Fields are compared unless they are known positions, so a field added later makes storages differ: the
 * failure direction is that more files are analysed than needed.
 *
 * @psalm-import-type GeneratedNames from IssueDiff
 * @internal
 */
final class StorageFingerprint
{
    /** Positions and position-keyed data, by property name */
    private const POSITION_FIELDS = [
        'hash' => true,
        'offset_start' => true,
        'offset_end' => true,
        'line_number' => true,
        'start_offset' => true,
        'end_offset' => true,
        'uses_start' => true,
        'uses_end' => true,
        'namespace_first_stmt_start' => true,
        'docblock_line_number' => true,
        // the issues the scanner found in the file, which no other file observes, and which a storage taken from
        // the cache and one scanned again do not hold alike
        'scan_issues' => true,
    ];

    /** Arrays keyed by an offset in the file */
    private const OFFSET_KEYED_FIELDS = [
        'suppressed_issues' => true,
        'namespace_aliases' => true,
    ];

    private const MAX_DEPTH = 60;

    private static int $too_deep = 0;

    /**
     * @return array{file: mixed, classlikes: mixed}|null null if the file is not scanned
     */
    public static function getSignature(Codebase $codebase, string $file_path): ?array
    {
        try {
            $file_storage = $codebase->file_storage_provider->get($file_path);
        } catch (InvalidArgumentException) {
            return null;
        }

        $classlikes = [];
        foreach ($file_storage->classlikes_in_file as $fq_classlike_name) {
            try {
                $classlikes[strtolower($fq_classlike_name)]
                    = $codebase->classlike_storage_provider->get($fq_classlike_name);
            } catch (InvalidArgumentException) {
                $classlikes[strtolower($fq_classlike_name)] = null;
            }
        }
        ksort($classlikes);
        $generated = IssueDiff::getGeneratedNames([$file_path]);

        return [
            'file' => self::withoutPositions($codebase, $generated, $file_storage),
            'classlikes' => self::withoutPositions($codebase, $generated, $classlikes),
        ];
    }

    /**
     * The paths of the leaves two signatures differ in, with what differs: "changed", or the side a key is on
     *
     * @return list<array{string, 'changed'|'added'|'removed'}>
     */
    public static function getDifferences(mixed $before, mixed $after, string $at = ''): array
    {
        if (is_array($before) && is_array($after)) {
            $differences = [];
            foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $key) {
                $path = $at . '/' . $key;

                if (!array_key_exists($key, $before)) {
                    $differences[] = [$path, 'added'];
                } elseif (!array_key_exists($key, $after)) {
                    $differences[] = [$path, 'removed'];
                } else {
                    $differences = [...$differences, ...self::getDifferences($before[$key], $after[$key], $path)];
                }
            }

            return $differences;
        }

        return serialize($before) === serialize($after) ? [] : [[$at, 'changed']];
    }

    /**
     * @param GeneratedNames $generated
     * @return scalar|array<array-key, mixed>|null
     */
    private static function withoutPositions(
        Codebase $codebase,
        array $generated,
        mixed $value,
        int $depth = 0,
    ): string|int|float|bool|array|null {
        if ($value instanceof CodeLocation) {
            return null;
        }

        if ($value instanceof Union) {
            return [
                self::normalizeString($value->getId(true), $generated),
                $value->from_docblock,
                $value->possibly_undefined,
                $value->by_ref,
                $value->ignore_nullable_issues,
                $value->ignore_falsable_issues,
            ];
        }

        if ($value instanceof Atomic) {
            return self::normalizeString($value->getId(true), $generated);
        }

        if ($value instanceof CodeIssue) {
            return [$value::class, self::normalizeString($value->message, $generated)];
        }

        if ($value instanceof UnresolvedConstantComponent) {
            // the scanner resolves a constant expression at once when it knows the classes it names, and leaves it
            // for later otherwise: the same value either way
            try {
                return self::withoutPositions(
                    $codebase,
                    $generated,
                    new Union([ConstantTypeResolver::resolve($codebase->classlikes, $value)]),
                    $depth,
                );
            } catch (Throwable) {
                return ['#' => $value::class, 'unresolved' => serialize($value)];
            }
        }

        if ($depth > self::MAX_DEPTH) {
            // what is not compared differs
            return '*too deep* ' . ++self::$too_deep;
        }

        if (is_object($value)) {
            $data = ['#' => $value::class];
            $properties = (array) $value;
            foreach (array_keys($properties) as $key) {
                $nul = strrpos((string) $key, "\0");
                $name = $nul === false ? (string) $key : substr((string) $key, $nul + 1);

                if (isset(self::POSITION_FIELDS[$name])) {
                    continue;
                }

                $data[$name] = isset(self::OFFSET_KEYED_FIELDS[$name]) && is_array($properties[$key])
                    ? self::withoutPositions($codebase, $generated, array_values($properties[$key]), $depth + 1)
                    : self::withoutPositions($codebase, $generated, $properties[$key], $depth + 1);
            }

            return $data;
        }

        if (is_array($value)) {
            $data = [];
            $positioned = 0;
            foreach (array_keys($value) as $key) {
                $normalized_key = is_string($key) ? self::normalizeString($key, $generated) : $key;
                // several closures or anonymous classes of a file, in file order
                $data[$normalized_key === $key ? $key : $normalized_key . '#' . $positioned++]
                    = self::withoutPositions($codebase, $generated, $value[$key], $depth + 1);
            }

            return $data;
        }

        if (is_string($value)) {
            return self::normalizeString($value, $generated);
        }

        // resources and closed resources are not part of a storage
        return is_scalar($value) ? $value : null;
    }

    /**
     * Names that contain the position of what they name: closures and anonymous classes of the file
     *
     * @param GeneratedNames $generated
     * @psalm-pure
     */
    private static function normalizeString(string $value, array $generated): string
    {
        return IssueDiff::normalizeGeneratedNames($value, $generated);
    }
}
