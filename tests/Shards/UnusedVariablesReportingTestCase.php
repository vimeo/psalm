<?php

declare(strict_types=1);

namespace Psalm\Tests\Shards;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

/**
 * What the shards of AnnotationTest and UnusedVariableTest share: they run their cases with unused variables
 * reported, as those classes do.
 */
abstract class UnusedVariablesReportingTestCase extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->project_analyzer->getCodebase()->reportUnusedVariables();
    }
}
