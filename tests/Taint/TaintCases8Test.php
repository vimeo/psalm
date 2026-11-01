<?php

declare(strict_types=1);

namespace Psalm\Tests\Taint;

use Override;
use Psalm\Tests\TaintTest;

final class TaintCases8Test extends TaintCasesTestCase
{
    /**
     * @psalm-pure
     */
    #[Override]
    public static function validCases(): array
    {
        return self::shard(TaintTest::providerValidCodeParse(), 7);
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function invalidCases(): array
    {
        return self::shard(TaintTest::providerInvalidCodeParse(), 7);
    }
}
