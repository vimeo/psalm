<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Identifier;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Type\Atomic\TNamedObject;

use function in_array;
use function str_starts_with;
use function strtolower;
use function substr;

/**
 * The entries of $_SERVER the client sends: the request headers (HTTP_*), the URI, and what is taken from it (the
 * CGI SAPI keeps the values it rewrites with an ORIG_ prefix, Apache those of a redirected request with a REDIRECT_
 * one), and argv, which PHP builds from the query string with register_argc_argv. The others come from the server.
 * Only a fetch of an entry tells them apart (see ArrayFetchAnalyzer::taintSuperGlobalFetch()).
 *
 * @internal
 * @psalm-immutable
 */
final class ServerVariableTaints
{
    private const SERVER_REQUEST = 'Psr\Http\Message\ServerRequestInterface';

    private const USER_CONTROLLED_KEYS = [
        'REQUEST_URI',
        'UNENCODED_URL',
        'DOCUMENT_URI',
        'QUERY_STRING',
        'argv',
        'PATH_INFO',
        'ORIG_PATH_INFO',
        'PATH_TRANSLATED',
        'ORIG_PATH_TRANSLATED',
        'ORIG_SCRIPT_NAME',
        'ORIG_SCRIPT_FILENAME',
        'PHP_SELF',
        'SCRIPT_URI',
        'SCRIPT_URL',
        'REDIRECT_URL',
        'REQUEST_METHOD',
        'CONTENT_TYPE',
        'PHP_AUTH_USER',
        'PHP_AUTH_PW',
        'PHP_AUTH_DIGEST',
    ];

    /**
     * @psalm-pure
     */
    public static function isUserControlled(string $key): bool
    {
        while ($key !== 'REDIRECT_URL' && str_starts_with($key, 'REDIRECT_')) {
            $key = substr($key, 9);
        }

        return str_starts_with($key, 'HTTP_') || in_array($key, self::USER_CONTROLLED_KEYS, true);
    }

    /**
     * Whether $expr gets the server params of a PSR-7 request (`$request->getServerParams()`), which hold the
     * $_SERVER it is built from
     *
     * @psalm-capabilities read-props
     */
    public static function isServerParamsCall(StatementsAnalyzer $statements_analyzer, Expr $expr): bool
    {
        if (!($expr instanceof MethodCall || $expr instanceof NullsafeMethodCall)
            || !$expr->name instanceof Identifier
            || strtolower($expr->name->name) !== 'getserverparams'
        ) {
            return false;
        }

        $codebase = $statements_analyzer->getCodebase();

        foreach ($statements_analyzer->node_data->getType($expr->var)?->getAtomicTypes() ?? [] as $atomic) {
            if ($atomic instanceof TNamedObject
                && $codebase->classlike_storage_provider->has($atomic->value)
            ) {
                $storage = $codebase->classlike_storage_provider->get($atomic->value);

                if ($storage->name === self::SERVER_REQUEST
                    || isset($storage->class_implements[strtolower(self::SERVER_REQUEST)])
                    || isset($storage->parent_interfaces[strtolower(self::SERVER_REQUEST)])
                ) {
                    return true;
                }
            }
        }

        return false;
    }
}
