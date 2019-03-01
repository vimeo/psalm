<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\IssueBuffer;
use Psalm\Issue\InvalidReturnType;
use Psalm\StatementsSource;
use Psalm\Internal\Analyzer\Statements\Expression\AssertionFinder;
use Psalm\Type;
use Psalm\Type\Reconciler;

class SimpleXmlElementAsXml implements \Psalm\Plugin\Hook\MethodReturnTypeProviderInterface
{
    public static function getClassLikeNames() : array
    {
        return ['SimpleXMLElement'];
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     * @return ?Type\Union
     */
    public static function getMethodReturnType(
        StatementsSource $source,
        string $fq_classlike_name,
        string $method_name_lowercase,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ) {
        if ($method_name_lowercase === 'asxml'
            && !count($call_args)
        ) {
            return Type::parseString('string|false');
        }
    }
}
