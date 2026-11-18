<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Issue\CodeIssue;
use Psalm\Issue\InsecureCookie;
use Psalm\Issue\WeakCipher;
use Psalm\Issue\WeakHash;
use Psalm\Issue\WeakRandom;
use Psalm\Issue\XmlExternalEntity;
use Psalm\IssueBuffer;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;

use function count;
use function implode;
use function in_array;
use function preg_match;
use function strtolower;

use const LIBXML_DTDLOAD;
use const LIBXML_NOENT;

/**
 * Reports the calls of builtins that make the code insecure whatever data they are given: weak hashes and ciphers,
 * seeded random number generators, cookies sent without the secure flag and XML parsers loading external entities.
 *
 * @internal
 */
final class InsecureCallAnalyzer
{
    /** The functions hashing with a weak algorithm, whatever their arguments */
    private const WEAK_HASH_FUNCTIONS = ['md5', 'md5_file', 'sha1', 'sha1_file'];

    /** The parameter of the functions that choose a hash algorithm, by its name before PHP 8 too */
    private const HASH_ALGORITHM_PARAMS = [
        'hash' => ['algo'],
        'hash_file' => ['algo'],
        'hash_init' => ['algo'],
        'openssl_digest' => ['digest_algo', 'method'],
    ];

    /** The hash algorithms that collisions break (lowercase) */
    private const WEAK_HASH_ALGORITHMS = ['md2', 'md4', 'md5', 'sha1'];

    /** The parameter of the functions that choose a cipher, by its name before PHP 8 too */
    private const CIPHER_PARAMS = [
        'openssl_encrypt' => ['cipher_algo', 'method'],
        'openssl_decrypt' => ['cipher_algo', 'method'],
    ];

    /**
     * The ciphers with a key or a block too small (DES, triple DES, RC2, RC4, Blowfish, CAST5, IDEA) and the ECB mode,
     * which encrypts equal blocks the same way
     */
    private const WEAK_CIPHER_PATTERN = '/^(des|rc2|rc4|bf|blowfish|cast5|idea)\b|-ecb$/i';

    /**
     * The functions and methods seeding the Mersenne Twister of mt_rand(), rand(), shuffle()... or a Random\Randomizer,
     * which then generates the same values for the same seed
     */
    private const RANDOM_SEED_PARAMS = [
        'mt_srand' => 'seed',
        'srand' => 'seed',
        'random\\engine\\mt19937::__construct' => 'seed',
    ];

    /** The functions setting a cookie, which take its secure flag or an array of options */
    private const COOKIE_FUNCTIONS = ['setcookie', 'setrawcookie'];

    /** The parameter of the functions and methods parsing XML that takes the libxml options */
    private const XML_OPTIONS_PARAMS = [
        'simplexml_load_string' => 'options',
        'simplexml_load_file' => 'options',
        'simplexmlelement::__construct' => 'options',
        'domdocument::load' => 'options',
        'domdocument::loadxml' => 'options',
        'dom\\xmldocument::createfromfile' => 'options',
        'dom\\xmldocument::createfromstring' => 'options',
    ];

    /**
     * @param array<int, PhpParser\Node\Arg> $args
     * @param array<int, FunctionLikeParameter> $function_params
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        string|MethodIdentifier $method_id,
        array $args,
        array $function_params,
        CodeLocation $code_location,
    ): void {
        if ($method_id instanceof MethodIdentifier) {
            $declaring_method_id = $statements_analyzer->getCodebase()->methods->getDeclaringMethodId($method_id);
            $id = strtolower((string) ($declaring_method_id ?? $method_id));
        } else {
            $id = strtolower($method_id);
        }

        $issue = null;

        if (in_array($id, self::WEAK_HASH_FUNCTIONS, true)) {
            $issue = new WeakHash(
                $id . '() hashes with a weak algorithm: use hash() with a SHA-2 or SHA-3 one, password_hash() for'
                    . ' passwords, or xxh3 where only speed matters',
                $code_location,
            );
        } elseif (isset(self::HASH_ALGORITHM_PARAMS[$id])
            && ($algorithms = self::getLiteralStrings(
                $statements_analyzer,
                self::getArg($args, $function_params, self::HASH_ALGORITHM_PARAMS[$id]),
            ))
        ) {
            foreach ($algorithms as $algorithm) {
                if (in_array(strtolower($algorithm), self::WEAK_HASH_ALGORITHMS, true)) {
                    $issue = new WeakHash(
                        $algorithm . ' is a weak hash algorithm: use a SHA-2 or SHA-3 one, password_hash() for'
                            . ' passwords, or xxh3 where only speed matters',
                        $code_location,
                    );
                    break;
                }
            }
        } elseif (isset(self::CIPHER_PARAMS[$id])
            && ($ciphers = self::getLiteralStrings(
                $statements_analyzer,
                self::getArg($args, $function_params, self::CIPHER_PARAMS[$id]),
            ))
        ) {
            foreach ($ciphers as $cipher) {
                if (preg_match(self::WEAK_CIPHER_PATTERN, $cipher) === 1) {
                    $issue = new WeakCipher(
                        $cipher . ' is a weak cipher: use an authenticated one, such as aes-256-gcm',
                        $code_location,
                    );
                    break;
                }
            }
        } elseif (isset(self::RANDOM_SEED_PARAMS[$id])
            && self::isGivenASeed(
                $statements_analyzer,
                ArgumentsAnalyzer::getArgForParam($args, $function_params, self::RANDOM_SEED_PARAMS[$id]),
                $id === 'random\\engine\\mt19937::__construct'
                    || $statements_analyzer->getCodebase()->analysis_php_version_id >= 8_03_00,
            )
        ) {
            $issue = new WeakRandom(
                'A seeded Mersenne Twister generates values whoever knows or guesses the seed can predict:'
                    . ' use random_int() or random_bytes() for values that must stay secret',
                $code_location,
            );
        } elseif (in_array($id, self::COOKIE_FUNCTIONS, true)
            && self::setsCookieWithoutSecureFlag($statements_analyzer, $args, $function_params)
        ) {
            $issue = new InsecureCookie(
                'The cookie is set without the secure flag, so it is also sent over plain HTTP',
                $code_location,
            );
        } elseif (isset(self::XML_OPTIONS_PARAMS[$id])
            && ($options = self::getLoadingExternalEntitiesOptions(
                $statements_analyzer,
                ArgumentsAnalyzer::getArgForParam($args, $function_params, self::XML_OPTIONS_PARAMS[$id]),
            ))
        ) {
            $issue = new XmlExternalEntity(
                implode(' and ', $options) . (count($options) === 1 ? ' makes' : ' make')
                    . ' the parser load the external entities of the document,'
                    . ' which can read local files or make requests (XXE)',
                $code_location,
            );
        }

        if ($issue instanceof CodeIssue) {
            IssueBuffer::maybeAdd($issue, $statements_analyzer->getSuppressedIssues());
        }
    }

    /**
     * Whether a seed is given: no seed seeds randomly, and so does null where $null_seeds_randomly (a null seed of
     * mt_srand() and srand() is 0 before PHP 8.3)
     *
     * @psalm-capabilities read-props
     */
    private static function isGivenASeed(
        StatementsAnalyzer $statements_analyzer,
        ?PhpParser\Node\Arg $arg,
        bool $null_seeds_randomly,
    ): bool {
        if ($arg === null) {
            return false;
        }

        if (!$null_seeds_randomly) {
            return true;
        }

        $type = $statements_analyzer->node_data->getType($arg->value);

        return $type === null || !$type->isNull();
    }

    /**
     * The argument given to the first of the parameters named $param_names the function has
     *
     * @param array<int, PhpParser\Node\Arg> $args
     * @param array<int, FunctionLikeParameter> $function_params
     * @param non-empty-list<string> $param_names
     * @psalm-mutation-free
     */
    private static function getArg(array $args, array $function_params, array $param_names): ?PhpParser\Node\Arg
    {
        foreach ($param_names as $param_name) {
            $arg = ArgumentsAnalyzer::getArgForParam($args, $function_params, $param_name);

            if ($arg !== null) {
                return $arg;
            }
        }

        return null;
    }

    /**
     * The literal strings the argument can be, or none if it can be another string or isn't given
     *
     * @return list<string>
     * @psalm-capabilities read-props
     */
    private static function getLiteralStrings(StatementsAnalyzer $statements_analyzer, ?PhpParser\Node\Arg $arg): array
    {
        $type = $arg !== null ? $statements_analyzer->node_data->getType($arg->value) : null;

        if ($type === null) {
            return [];
        }

        $values = [];

        foreach ($type->getAtomicTypes() as $atomic) {
            if ($atomic instanceof TLiteralString) {
                $values[] = $atomic->value;
            }
        }

        return $values;
    }

    /**
     * Whether setcookie() or setrawcookie() are called without the secure flag: it isn't given, neither as an
     * argument nor as an option, or it is false. Nothing is reported when the analysis can't tell.
     *
     * @param array<int, PhpParser\Node\Arg> $args
     * @param array<int, FunctionLikeParameter> $function_params
     * @psalm-capabilities read-props
     */
    private static function setsCookieWithoutSecureFlag(
        StatementsAnalyzer $statements_analyzer,
        array $args,
        array $function_params,
    ): bool {
        // the signature taking the options names the parameter `options`
        $options_arg = ArgumentsAnalyzer::getArgForParam($args, $function_params, 'expires_or_options')
            ?? ArgumentsAnalyzer::getArgForParam($args, $function_params, 'options');
        $options_type = $options_arg !== null ? $statements_analyzer->node_data->getType($options_arg->value) : null;

        if ($options_type !== null && $options_type->isArray()) {
            $options = $options_type->getSingleAtomic();

            if (!$options instanceof TKeyedArray || $options->fallback_params !== null) {
                return false;
            }

            return !isset($options->properties['secure']) || $options->properties['secure']->isFalse();
        }

        $secure_arg = ArgumentsAnalyzer::getArgForParam($args, $function_params, 'secure');

        if ($secure_arg === null) {
            return true;
        }

        $secure_type = $statements_analyzer->node_data->getType($secure_arg->value);

        return $secure_type !== null && $secure_type->isFalse();
    }

    /**
     * The names of the libxml options making a parser load external entities that the options given can set
     *
     * @return list<string>
     * @psalm-capabilities read-props
     */
    private static function getLoadingExternalEntitiesOptions(
        StatementsAnalyzer $statements_analyzer,
        ?PhpParser\Node\Arg $arg,
    ): array {
        $type = $arg !== null ? $statements_analyzer->node_data->getType($arg->value) : null;

        if ($type === null) {
            return [];
        }

        $set = 0;

        foreach ($type->getAtomicTypes() as $atomic) {
            if ($atomic instanceof TLiteralInt) {
                $set |= $atomic->value;
            }
        }

        $options = [];

        if ($set & LIBXML_NOENT) {
            $options[] = 'LIBXML_NOENT';
        }

        if ($set & LIBXML_DTDLOAD) {
            $options[] = 'LIBXML_DTDLOAD';
        }

        return $options;
    }
}
