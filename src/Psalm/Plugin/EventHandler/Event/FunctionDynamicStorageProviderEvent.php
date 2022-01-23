<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\StatementsAnalyzer;

class FunctionDynamicStorageProviderEvent
{
    private StatementsAnalyzer $statements_analyzer;
    private string $function_id;
    /** @var list<PhpParser\Node\Arg> */
    private array $call_args;
    private Context $context;
    private CodeLocation $code_location;

    /**
     * @param list<PhpParser\Node\Arg> $call_args
     */
    public function __construct(
        StatementsAnalyzer $statements_analyzer,
        string $function_id,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ) {
        $this->statements_analyzer = $statements_analyzer;
        $this->function_id = $function_id;
        $this->call_args = $call_args;
        $this->context = $context;
        $this->code_location = $code_location;
    }

    public function getStatementsAnalyzer(): StatementsAnalyzer
    {
        return $this->statements_analyzer;
    }

    public function getFunctionId(): string
    {
        return $this->function_id;
    }

    /**
     * @return list<PhpParser\Node\Arg>
     */
    public function getCallArgs(): array
    {
        return $this->call_args;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getCodeLocation(): CodeLocation
    {
        return $this->code_location;
    }
}
