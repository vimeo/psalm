<?php

declare(strict_types=1);

namespace Psalm\Tests\Storage;

use PHPUnit\Framework\TestCase;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\TemplateVariance;

use function array_map;

final class ClassLikeStorageTest extends TestCase
{
    public function testTheVarianceOfATemplateFollowsBothVarianceArrays(): void
    {
        $storage = new ClassLikeStorage('C');
        // as a plugin writes them: template_covariants stays where covariance is read from
        $storage->template_covariants = [0 => false, 1 => true, 3 => true];
        $storage->template_contravariants = [2 => true, 3 => true];

        self::assertSame(
            [
                TemplateVariance::Invariant,
                TemplateVariance::Covariant,
                TemplateVariance::Contravariant,
                TemplateVariance::Contravariant,
                TemplateVariance::Invariant,
            ],
            array_map($storage->getTemplateVariance(...), [0, 1, 2, 3, 4]),
        );
    }
}
