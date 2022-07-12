<?php

namespace Psalm\Tests\Template;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class Issue8200Test extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'return TemplatedClass<static>' => [
                '<?php

                /**
                 * @template-covariant A
                 * @psalm-immutable
                 */
                final class Maybe
                {
                    /**
                     * @param null|A $value
                     */
                    public function __construct(private $value = null) {}

                    /**
                     * @template B
                     * @param B $value
                     * @return Maybe<B>
                     *
                     * @psalm-pure
                     */
                    public static function just($value): self
                    {
                        return new self($value);
                    }
                }

                abstract class Test
                {
                    final private function __construct() {}

                    /** @return Maybe<static> */
                    final public static function create(): Maybe
                    {
                        return Maybe::just(new static());
                    }
                }',
            ],
        ];
    }
}
