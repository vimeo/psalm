<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Provider\ReturnTypeProvider;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ReflectionParameterGetTypeTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerInvalidCodeParse() : iterable
    {
        return [
            'withoutHasTypeCall' => [
                '<?php
                    $method = new ReflectionMethod(stdClass::class);
                    $parameters = $method->getParameters();

                    foreach ($parameters as $parameter) {
                        $parameter->getType()->__toString();
                    }',
                'error_message' => 'PossiblyNullReference',
            ],
            'withHasCallEqualsFalse' => [
                '<?php
                    $method = new ReflectionMethod(stdClass::class);
                    $parameters = $method->getParameters();

                    foreach ($parameters as $parameter) {
                        if (!$parameter->hasType()) {
                            $parameter->getType()->__toString();
                        }
                    }',
                'error_message' => 'NullReference',
            ],
        ];
    }

    public function providerValidCodeParse() : iterable
    {
        return [
            'withHasTypeCall' => [
                '<?php
                    $method = new ReflectionMethod(stdClass::class);
                    $parameters = $method->getParameters();

                    foreach ($parameters as $parameter) {
                        if ($parameter->hasType()) {
                            $parameter->getType()->__toString();
                        }
                    }',
            ],
        ];
    }
}
