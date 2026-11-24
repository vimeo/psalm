<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

/**
 * The API PHP 8.6 adds: its types, generics and capabilities
 */
final class Php86Test extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'pollWatcherKeepsTheTypesOfItsHandleAndData' => [
                'code' => '<?php
                    use Io\Poll\{Context, Event};

                    /** @param resource $server */
                    function watch($server): void {
                        $poll = new Context();
                        $watcher = $poll->add(new StreamPollHandle($server), [Event::Read], "server");
                        $stream = $watcher->getHandle()->getStream();
                        $data = $watcher->getData();
                    }',
                'assertions' => [],
                'ignored_issues' => ['UnusedVariable'],
                'php_version' => '8.6',
            ],
            'pollWatcherTypes' => [
                'code' => '<?php
                    use Io\Poll\{Context, Event};

                    $poll = new Context();
                    $watcher = $poll->add(new StreamPollHandle(STDIN), [Event::Read], 42);
                    $data = $watcher->getData();
                    $watchers = $poll->wait();',
                'assertions' => [
                    '$watcher===' => 'Io\Poll\Watcher<StreamPollHandle, 42>',
                    '$data===' => '42',
                    '$watchers===' => 'list<Io\Poll\Watcher<Io\Poll\Handle, mixed>>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'pollWatcherDataFollowsItsModifications' => [
                'code' => '<?php
                    use Io\Poll\{Context, Event};

                    $watcher = (new Context())->add(new StreamPollHandle(STDIN), [Event::Read], "server");
                    $watcher->modifyData(42);
                    $data = $watcher->getData();',
                'assertions' => [
                    '$watcher===' => 'Io\Poll\Watcher<StreamPollHandle, 42>',
                    '$data===' => '42',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'pureCodeCanUseDurationsAndBuilders' => [
                'code' => '<?php
                    /**
                     * @param positive-int $attempt
                     * @psalm-pure
                     */
                    function delay(int $attempt): Time\Duration {
                        return Time\Duration::fromSeconds(1)->multiplyBy(2 ** $attempt);
                    }

                    /** @psalm-pure */
                    function url(): Uri\Rfc3986\Uri {
                        $builder = new Uri\Rfc3986\UriBuilder();
                        $builder->setScheme("https");
                        $builder->setHost("example.com");

                        return $builder->build();
                    }

                    /** @psalm-pure */
                    function anonymize(string $ip, string $key): string {
                        return sodium_crypto_ipcrypt_encrypt($ip, $key);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'newReflectionMethodsOnlyReadTheReflectedSymbol' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function describe(ReflectionProperty $p, ReflectionParameter $param, ReflectionAttribute $a): bool {
                        return $p->isReadable(null)
                            && $param->getDocComment() !== false
                            && $a->inNamespace()
                            && $a->getShortName() !== "";
                    }',
                'assertions' => [],
                'ignored_issues' => ['MissingTemplateParam'],
                'php_version' => '8.6',
            ],
            'streamExceptionErrors' => [
                'code' => '<?php
                    function f(StreamException $e): void {
                        $errors = $e->getErrors();
                    }',
                'assertions' => [],
                'ignored_issues' => ['UnusedVariable'],
                'php_version' => '8.6',
            ],
            'streamLastErrors' => [
                'code' => '<?php
                    $errors = stream_last_errors();',
                'assertions' => [
                    '$errors===' => 'list<StreamError>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
            'waitingForEventsIsImpure' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function waitFor(Io\Poll\Context $poll): int {
                        return count($poll->wait());
                    }',
                'error_message' => 'ImpureMethodCall',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'readingTheLastStreamErrorsIsImpure' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function lastErrors(): array {
                        return stream_last_errors();
                    }',
                'error_message' => 'ImpureFunctionCall',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'generatingAKeyIsImpure' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function newKey(): string {
                        return sodium_crypto_ipcrypt_keygen();
                    }',
                'error_message' => 'ImpureFunctionCall',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'absorbingIntoAXofStateIsImpure' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function absorb(SodiumCryptoXofShake128State $state): bool {
                        sodium_crypto_xof_shake128_update($state, "data");

                        return true;
                    }',
                'error_message' => 'ImpureFunctionCall',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'newMethodOfAnOlderClassIsUndefinedBeforePhp86' => [
                'code' => '<?php
                    /** @param ReflectionAttribute<object> $a */
                    function f(ReflectionAttribute $a): bool {
                        return $a->inNamespace();
                    }',
                'error_message' => 'Method ReflectionAttribute::inNamespace is not defined for the analysed PHP version 8.5',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
        ];
    }
}
