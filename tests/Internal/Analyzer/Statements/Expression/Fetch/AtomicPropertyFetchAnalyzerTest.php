<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Analyzer\Statements\Expression\Fetch;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class AtomicPropertyFetchAnalyzerTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'allowDynamicProperties' => [
                'code' => '<?php
                    /** @property-read string $foo */
                    #[\AllowDynamicProperties]
                    class A {
                        public function __construct(string $key, string $value)
                        {
                            $this->$key = $value;
                        }
                    }

                    echo (new A("foo", "bar"))->foo;
                    ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'allowDynamicProperties for child' => [
                'code' => '<?php
                    /** @property-read string $foo */
                    #[\AllowDynamicProperties]
                    class A {
                        public function __construct(string $key, string $value)
                        {
                            $this->$key = $value;
                        }
                    }

                    class B extends A {}

                    echo (new B("foo", "bar"))->foo;
                    ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'allowDynamicProperties for grandchild' => [
                'code' => '<?php
                    /** @property-read string $foo */
                    #[\AllowDynamicProperties]
                    class A {
                        public function __construct(string $key, string $value)
                        {
                            $this->$key = $value;
                        }
                    }

                    class B extends A {}
                    class C extends B {}

                    echo (new C("foo", "bar"))->foo;
                    ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'undefinedPropertyAccessOnMissingDependency' => [
                'code' => <<<'PHP'
                    <?php
                    class A extends Missing {}
                    function make(): A { return new A; }

                    make()->prop;
                    PHP,
                'error_message' => 'UndefinedPropertyFetch',
                'ignored_issues' => ['MissingDependency'],
            ],
        ];
    }
}
