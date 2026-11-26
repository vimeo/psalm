<?php

declare(strict_types=1);

namespace Psalm\Tests\Config\Plugin\EventHandler\TaintedCall;

/**
 * The second shard of the cases of TaintedCallTest, which runs the first (see ProviderShard).
 */
final class TaintedCallCases2Test extends TaintedCallTest
{
    protected const SHARD = 1;
}
