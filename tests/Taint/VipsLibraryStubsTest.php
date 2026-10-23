<?php

declare(strict_types=1);

namespace Psalm\Tests\Taint;

use Override;

/**
 * The stub of stubs/libraries for jcupitt/vips, which Psalm loads because the code below declares classes
 * of the package, as its sources do
 */
final class VipsLibraryStubsTest extends LibraryStubsTestCase
{
    /**
     * @psalm-pure
     */
    #[Override]
    protected static function getLibraries(): string
    {
        return '<?php
            namespace Jcupitt\Vips {
                abstract class ImageAutodoc {}
                class Image extends ImageAutodoc implements \ArrayAccess {
                    public function offsetExists(mixed $offset): bool {
                        return false;
                    }

                    public function offsetGet(mixed $offset): mixed {
                        return null;
                    }

                    public function offsetSet(mixed $offset, mixed $value): void {}

                    public function offsetUnset(mixed $offset): void {}
                }
            }';
    }

    /**
     * @return array<string, array{string, list<string>}>
     * @psalm-pure
     */
    #[Override]
    public static function providerTaints(): array
    {
        return [
            'files' => [
                '<?php
                    $image = Jcupitt\Vips\Image::newFromFile((string) $_GET["in"]);
                    $image->writeToFile((string) $_GET["out"]);
                    $image->writeToFile("/tmp/thumbnail.jpg");',
                [
                    'TaintedFile on line 2',
                    'TaintedFile on line 3',
                ],
            ],
        ];
    }
}
