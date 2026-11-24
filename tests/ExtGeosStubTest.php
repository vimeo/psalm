<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class ExtGeosStubTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'classesWithTheirCallMapMethods' => [
                'code' => <<<'PHP'
                    <?php
                    $reader = new GEOSWKTReader();
                    $geometry = $reader->read('POINT(0 0)');
                    $area = $geometry->area();
                    $wkt = (new GEOSWKTWriter())->write($geometry);
                    $wkb = (new GEOSWKBWriter())->write($geometry);
                    $fromWkb = (new GEOSWKBReader())->read($wkb);
                    PHP,
                'assertions' => [
                    '$geometry===' => 'GEOSGeometry',
                    '$area===' => 'float',
                    '$wkt===' => 'string',
                    '$fromWkb===' => 'GEOSGeometry',
                ],
            ],
        ];
    }
}
