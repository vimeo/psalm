<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

use Amp\Serialization\SerializationException;
use Amp\Serialization\Serializer;
use Override;
use Throwable;

use function fwrite;
use function get_class;
use function get_resource_type;
use function gettype;
use function igbinary_serialize;
use function igbinary_unserialize;
use function is_array;
use function is_object;
use function is_resource;
use function preg_replace;
use function spl_object_id;
use function sprintf;

use const STDERR;

/**
 * @internal
 * @psalm-immutable
 */
final class IgbinarySerializer implements Serializer
{
    #[Override]
    public function serialize(mixed $data): string
    {
        try {
            $data = igbinary_serialize($data);
            if ($data === false) {
                throw new SerializationException("Could not serialize data!");
            }
            return $data;
        } catch (Throwable $exception) {
            self::debugUnserializable($data);
            throw new SerializationException(
                sprintf(
                    'The given data could not be serialized: %s',
                    $exception->getMessage(),
                ),
                0,
                $exception,
            );
        }
    }

    /**
     * TEMPORARY DEBUG (PR #11955 CI crash): report where the payload holds a
     * resource, and any exception it carries (a worker TaskFailure hides the
     * original error behind this serialization failure).
     *
     * @psalm-suppress all
     */
    private static function debugUnserializable(mixed $data): void
    {
        $seen = [];
        $walk = static function (mixed $v, string $path, int $depth) use (&$walk, &$seen): void {
            if (is_resource($v) || gettype($v) === 'resource (closed)') {
                fwrite(STDERR, "PSALM-DEBUG resource at $path: " . get_resource_type($v) . "\n");
                return;
            }
            if ($depth > 60) {
                return;
            }
            if (is_object($v)) {
                $id = spl_object_id($v);
                if (isset($seen[$id])) {
                    return;
                }
                $seen[$id] = true;
                if ($v instanceof Throwable) {
                    fwrite(STDERR, "PSALM-DEBUG throwable at $path: " . get_class($v) . ': ' . $v->getMessage()
                        . ' @ ' . $v->getFile() . ':' . $v->getLine() . "\n" . $v->getTraceAsString() . "\n");
                }
                foreach ((array) $v as $k => $x) {
                    $walk($x, $path . '->' . preg_replace('/^\\0.*\\0/', '', (string) $k)
                        . '(' . get_class($v) . ')', $depth + 1);
                }
                return;
            }
            if (is_array($v)) {
                foreach ($v as $k => $x) {
                    $walk($x, $path . '[' . $k . ']', $depth + 1);
                }
            }
        };
        $walk($data, '$', 0);
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function unserialize(string $data): mixed
    {
        try {
            return igbinary_unserialize($data);
        } catch (Throwable $exception) {
            throw new SerializationException(
                'Exception thrown when unserializing data',
                0,
                $exception,
            );
        }
    }
}
