<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Context;
use Psalm\IssueBuffer;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function array_filter;
use function array_map;
use function explode;
use function implode;
use function str_replace;

use const DIRECTORY_SEPARATOR;

/**
 * Native symbols stubbed with an `@since x.y` tag are reported as undefined when analysing an
 * older PHP version without a polyfill, while analysis still uses their stubbed shape.
 */
final class NativeSymbolAvailabilityTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * Unused-result reporting needs find_unused_variables, which the data-provider harness leaves off.
     */
    public function testDiscardedDurationResultIsReported(): void
    {
        $this->project_analyzer->setPhpVersion('8.6', 'tests');
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->find_unused_variables = true;
        $codebase->config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                function f(Time\Duration $d): void {
                    $d->add(Time\Duration::fromSeconds(1));
                    $d->negate();
                    $d->seconds;
                }
            ',
        );

        $this->analyzeFile('somefile.php', new Context());

        $unused_calls = array_filter(
            IssueBuffer::getIssuesData()['somefile.php'] ?? [],
            static fn($issue): bool => $issue->type === 'UnusedMethodCall',
        );

        $this->assertCount(2, $unused_calls);
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        yield 'classAvailableFromItsVersion' => [
            'code' => '<?php
                $m = new WeakMap();
                $c = $m->count();
            ',
            'assertions' => [
                '$c===' => 'int<0, max>',
            ],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];

        yield 'classPolyfilledBelowItsVersion' => [
            'code' => '<?php
                final class WeakMap implements Countable {
                    public function count(): int { return 0; }
                }

                $m = new WeakMap();
                $c = $m->count();
            ',
            'assertions' => [
                '$c===' => 'int',
            ],
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];

        yield 'classPolyfilledByProjectBelowItsVersion' => [
            'code' => '<?php
                interface Stringable {
                    public function __toString(): string;
                }

                final class A implements Stringable {
                    public function __toString(): string { return ""; }
                }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];

        yield 'stubShapeKeptBelowItsVersion' => [
            'code' => '<?php
                /** @psalm-suppress UndefinedClass, UndefinedPropertyFetch */
                function f(UnitEnum $e): string {
                    return $e->name;
                }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];

        yield 'guardedByPhpVersionId' => [
            'code' => '<?php
                function a(): ?object {
                    if (PHP_VERSION_ID >= 80000) {
                        return new WeakMap();
                    }

                    return null;
                }

                function b(): ?object {
                    return \\PHP_VERSION_ID >= 80000 ? new WeakMap() : null;
                }

                function c(): object {
                    if (PHP_VERSION_ID < 80000) {
                        throw new RuntimeException();
                    }

                    return new WeakMap();
                }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];

        yield 'closureCreatedUnderAPhpVersionIdGuard' => [
            'code' => '<?php
                function a(): ?Closure {
                    if (PHP_VERSION_ID >= 80000) {
                        return function (): object {
                            return new WeakMap();
                        };
                    }

                    return null;
                }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];

        yield 'guardedByClassExists' => [
            'code' => '<?php
                function a(): ?object {
                    if (class_exists(WeakMap::class)) {
                        return new WeakMap();
                    }

                    return null;
                }

                function b(): object {
                    if (!class_exists("WeakMap")) {
                        throw new RuntimeException();
                    }

                    return new WeakMap();
                }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];

        yield 'callMapFunctionAvailableFromItsVersion' => [
            'code' => '<?php
                $x = fdiv(1.0, 2.0);
            ',
            'assertions' => [
                '$x===' => 'float',
            ],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];

        yield 'callMapFunctionPolyfilledBelowItsVersion' => [
            'code' => '<?php
                if (!function_exists("fdiv")) {
                    function fdiv(float $num1, float $num2): float {
                        return $num1 / $num2;
                    }
                }

                $x = fdiv(1.0, 2.0);
            ',
            'assertions' => [
                '$x===' => 'float',
            ],
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];

        yield 'callMapSymbolsGuarded' => [
            'code' => '<?php
                function a(float $f): float {
                    return PHP_VERSION_ID >= 80000 ? fdiv($f, 2.0) : $f / 2.0;
                }

                function b(ReflectionClass $r): bool {
                    if (PHP_VERSION_ID >= 80100 && $r->isEnum()) {
                        return true;
                    }

                    return false;
                }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];

        // PhpVersionedClasses.phpstub is parsed with the target version's grammar, so it must stay free of
        // syntax (enums) that older targets cannot parse, or all its stubs would be lost.
        yield 'versionedClassStubsLoadOnPhp74' => [
            'code' => '<?php
                function f(): ?int {
                    if (class_exists(SensitiveParameterValue::class)) {
                        return (new SensitiveParameterValue(123))->getValue();
                    }

                    return null;
                }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];

        yield 'php86DurationAvailable' => [
            'code' => '<?php
                function f(Time\Duration $a): int {
                    $b = Time\Duration::fromSeconds(1, 500)
                        ->add(Time\Duration::fromMinutes(2))
                        ->sub(Time\Duration::fromMilliseconds(3))
                        ->negate()
                        ->absolute()
                        ->multiplyBy(2)
                        ->divideBy(2);
                    $c = Time\Duration::fromIso8601DurationString("PT1S");
                    $total = $a->seconds + $b->nanoseconds + $c->seconds;
                    if ($b->negative) {
                        return -$total;
                    }

                    return $total + Time\Duration::compare($a, $b);
                }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.6',
        ];

        yield 'php86DurationPureFactories' => [
            'code' => '<?php
                /** @psalm-pure */
                function f(int $seconds): Time\Duration {
                    return Time\Duration::fromSeconds($seconds);
                }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.6',
        ];

        yield 'php86SortDirectionAvailable' => [
            'code' => '<?php
                function f(SortDirection $d): string {
                    return match ($d) {
                        SortDirection::Ascending => "asc",
                        SortDirection::Descending => "desc",
                    };
                }

                $cases = SortDirection::cases();
            ',
            'assertions' => [
                '$cases===' => 'list{enum(SortDirection::Ascending), enum(SortDirection::Descending)}',
            ],
            'ignored_issues' => [],
            'php_version' => '8.6',
        ];

        yield 'php86PollApiAvailable' => [
            'code' => '<?php
                /** @param resource $stream */
                function f($stream): void {
                    $context = new Io\Poll\Context(Io\Poll\Backend::Auto);
                    $watcher = $context->add(
                        new StreamPollHandle($stream),
                        [Io\Poll\Event::Read, Io\Poll\Event::Write],
                        "data",
                    );
                    $watcher->modify([Io\Poll\Event::Read]);
                    $watcher->modifyEvents([Io\Poll\Event::Read]);
                    $watcher->modifyData(1);

                    foreach ($context->wait(Time\Duration::fromMilliseconds(10), 5) as $triggered) {
                        foreach ($triggered->getTriggeredEvents() as $event) {
                            if ($event === Io\Poll\Event::Read && $triggered->hasTriggered($event)) {
                                $handle = $triggered->getHandle();
                            }
                        }
                    }

                    $watcher->remove();
                    $backends = Io\Poll\Backend::getAvailableBackends();
                    $supports = $context->getBackend()->supportsEdgeTriggering();
                }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.6',
        ];

        yield 'php86PollExceptionHierarchy' => [
            'code' => '<?php
                function f(Io\Poll\Context $context): void {
                    try {
                        $context->wait();
                    } catch (Io\Poll\FailedPollWaitException $e) {
                        echo $e->getCode() === Io\Poll\FailedPollOperationException::ERROR_TIMEOUT;
                    } catch (Io\Poll\FailedPollOperationException | Io\Poll\InactiveWatcherException $e) {
                        throw $e;
                    } catch (Io\Poll\PollException $e) {
                        throw new Io\IoException("", 0, $e);
                    } catch (Time\TimeException $e) {
                        echo $e->getMessage();
                    }
                }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.6',
        ];

        $streamErrorCodeArms = implode(
            "\n                        ",
            array_map(
                static fn(string $case): string => 'StreamErrorCode::' . $case . ' => "' . $case . '",',
                explode(
                    ' ',
                    'None Generic ReadFailed WriteFailed SeekFailed SeekNotSupported FlushFailed TruncateFailed '
                    . 'ConnectFailed BindFailed ListenFailed AcceptFailed NotWritable NotReadable Disabled NotFound '
                    . 'PermissionDenied AlreadyExists InvalidPath PathTooLong OpenFailed CreateFailed DupFailed '
                    . 'UnlinkFailed RenameFailed MkdirFailed RmdirFailed StatFailed MetaFailed ChmodFailed '
                    . 'ChownFailed CopyFailed TouchFailed InvalidMode InvalidMeta ModeNotSupported Readonly '
                    . 'RecursionDetected NotImplemented NoOpener PersistentNotSupported WrapperNotFound '
                    . 'WrapperDisabled ProtocolUnsupported WrapperRegistrationFailed WrapperUnregistrationFailed '
                    . 'WrapperRestorationFailed FilterNotFound FilterFailed CastFailed CastNotSupported '
                    . 'MakeSeekableFailed BufferedDataLost NetworkSendFailed NetworkRecvFailed SslNotSupported '
                    . 'ResumptionFailed SocketPathTooLong OobNotSupported ProtocolError InvalidUrl InvalidResponse '
                    . 'InvalidHeader InvalidParam RedirectLimit AuthFailed TimeOut ArchivingFailed EncodingFailed '
                    . 'DecodingFailed InvalidFormat AllocationFailed TemporaryFileFailed LockFailed '
                    . 'LockNotSupported UserspaceNotImplemented UserspaceInvalidReturn UserspaceCallFailed',
                ),
            ),
        );

        yield 'php86StreamErrorsAvailable' => [
            'code' => '<?php
                function code(StreamErrorCode $code): string {
                    return match ($code) {
                        ' . $streamErrorCodeArms . '
                    };
                }

                function mode(StreamErrorMode $mode): int {
                    return match ($mode) {
                        StreamErrorMode::Error => 1,
                        StreamErrorMode::Exception => 2,
                        StreamErrorMode::Silent => 3,
                    };
                }

                function store(StreamErrorStore $store): int {
                    return match ($store) {
                        StreamErrorStore::Auto => 1,
                        StreamErrorStore::None => 2,
                        StreamErrorStore::NonTerminating => 3,
                        StreamErrorStore::Terminating => 4,
                        StreamErrorStore::All => 5,
                    };
                }

                function f(StreamException $e): void {
                    foreach ($e->getErrors() as $error) {
                        echo code($error->code), $error->message, $error->wrapperName, $error->severity;
                        if ($error->terminating) {
                            throw new StreamException($e->getMessage(), $e->getCode(), $e);
                        }
                    }
                }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.6',
        ];

        yield 'php86UriBuildersAvailable' => [
            'code' => '<?php
                function h(Uri\Rfc3986\UriBuilder $b, StreamError $e, Openssl\Session $s): void {}

                function rfc3986(Uri\Rfc3986\UriBuilder $builder): void {
                    $builder->reset()
                        ->setScheme("https")
                        ->setUserInfo("user:secret")
                        ->setHost("example.com")
                        ->setPort(8443)
                        ->setPath("/a")
                        ->setQuery(null)
                        ->setFragment("top")
                        ->build(null);
                }

                function whatwg(Uri\WhatWg\UrlBuilder $builder): void {
                    $builder->reset()
                        ->setScheme("https")
                        ->setUsername("user")
                        ->setPassword("secret")
                        ->setHost("example.com")
                        ->setPort(null)
                        ->setPath("/a")
                        ->setQuery("q=1")
                        ->setFragment(null)
                        ->build(null, $softErrors);
                    echo count($softErrors);
                }

                function rfc3986HostType(Uri\Rfc3986\UriHostType $type): string {
                    return match ($type) {
                        Uri\Rfc3986\UriHostType::IpV4 => "ipv4",
                        Uri\Rfc3986\UriHostType::IpV6 => "ipv6",
                        Uri\Rfc3986\UriHostType::IpVFuture => "ipvfuture",
                        Uri\Rfc3986\UriHostType::RegisteredName => "name",
                    };
                }

                function rfc3986Type(Uri\Rfc3986\UriType $type): string {
                    return match ($type) {
                        Uri\Rfc3986\UriType::AbsolutePathReference => "absolute-path",
                        Uri\Rfc3986\UriType::RelativePathReference => "relative-path",
                        Uri\Rfc3986\UriType::NetworkPathReference => "network-path",
                        Uri\Rfc3986\UriType::Uri => "uri",
                    };
                }

                function whatwgHostType(Uri\WhatWg\UrlHostType $type): string {
                    return match ($type) {
                        Uri\WhatWg\UrlHostType::IpV4 => "ipv4",
                        Uri\WhatWg\UrlHostType::IpV6 => "ipv6",
                        Uri\WhatWg\UrlHostType::Domain => "domain",
                        Uri\WhatWg\UrlHostType::Opaque => "opaque",
                        Uri\WhatWg\UrlHostType::Empty => "empty",
                    };
                }

                function whatwgEncodingMode(Uri\WhatWg\UrlPercentEncodingMode $mode): string {
                    return match ($mode) {
                        Uri\WhatWg\UrlPercentEncodingMode::Username => "username",
                        Uri\WhatWg\UrlPercentEncodingMode::Password => "password",
                        Uri\WhatWg\UrlPercentEncodingMode::OpaqueHost => "opaque-host",
                        Uri\WhatWg\UrlPercentEncodingMode::Path => "path",
                        Uri\WhatWg\UrlPercentEncodingMode::OpaquePath => "opaque-path",
                        Uri\WhatWg\UrlPercentEncodingMode::PathSegment => "path-segment",
                        Uri\WhatWg\UrlPercentEncodingMode::Query => "query",
                        Uri\WhatWg\UrlPercentEncodingMode::SpecialQuery => "special-query",
                        Uri\WhatWg\UrlPercentEncodingMode::FormQuery => "form-query",
                        Uri\WhatWg\UrlPercentEncodingMode::Fragment => "fragment",
                    };
                }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.6',
        ];

        yield 'php86OpensslAvailable' => [
            'code' => '<?php
                function f(string $pem): int {
                    try {
                        $session = Openssl\Session::import($pem, OPENSSL_ENCODING_PEM);
                        $exported = $session->export();
                        $psk = new Openssl\Psk("key", "identity");
                        $len = strlen($psk->psk) + strlen($psk->identity ?? "") + strlen($session->id);
                        $max = Openssl\Psk::MAX_PSK_LEN + Openssl\Psk::MAX_IDENTITY_LEN;
                        if ($session->isResumable() && $session->hasTicket()) {
                            $len += $session->getTimeout() + $session->getCreatedAt();
                            $len += $session->getTicketLifetimeHint() ?? 0;
                            $len += strlen($session->getProtocol() ?? "") + strlen($session->getCipher() ?? "");
                        }

                        $session->__unserialize($session->__serialize());
                        return $len + $max + strlen($exported);
                    } catch (Openssl\OpensslException $e) {
                        throw new Openssl\OpensslException($e->getMessage(), $e->getCode(), $e);
                    }
                }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.6',
        ];

        yield 'php86IntlNumberRangeFormatterAvailable' => [
            'code' => '<?php
                function f(float $start, int $end): string {
                    $formatter = IntlNumberRangeFormatter::createFromSkeleton(
                        "currency/EUR",
                        "en",
                        IntlNumberRangeFormatter::COLLAPSE_AUTO,
                        IntlNumberRangeFormatter::IDENTITY_FALLBACK_RANGE,
                    );
                    $text = $formatter->format($start, $end);
                    if ($formatter->getErrorCode() !== 0) {
                        return $formatter->getErrorMessage();
                    }

                    return $text . IntlNumberRangeFormatter::COLLAPSE_NONE
                        . IntlNumberRangeFormatter::COLLAPSE_UNIT
                        . IntlNumberRangeFormatter::COLLAPSE_ALL
                        . IntlNumberRangeFormatter::IDENTITY_FALLBACK_SINGLE_VALUE
                        . IntlNumberRangeFormatter::IDENTITY_FALLBACK_APPROXIMATELY
                        . IntlNumberRangeFormatter::IDENTITY_FALLBACK_APPROXIMATELY_OR_SINGLE_VALUE;
                }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.6',
        ];

        yield 'php86SnmpEnumsAvailable' => [
            'code' => '<?php
                function mib(Snmp\Mib $option): int {
                    return match ($option) {
                        Snmp\Mib::AllowUnderscores => 1,
                        Snmp\Mib::CommentTerm => 2,
                        Snmp\Mib::Replace => 3,
                    };
                }

                function oidOutput(Snmp\OidOutput $format): int {
                    return match ($format) {
                        Snmp\OidOutput::Suffix => 1,
                        Snmp\OidOutput::Module => 2,
                        Snmp\OidOutput::Full => 3,
                        Snmp\OidOutput::Numeric => 4,
                        Snmp\OidOutput::Ucd => 5,
                        Snmp\OidOutput::None => 6,
                    };
                }

                function output(Snmp\Output $option): int {
                    return match ($option) {
                        Snmp\Output::NumericIndex => 1,
                        Snmp\Output::EnumPrint => 2,
                        Snmp\Output::EscapeQuotes => 3,
                        Snmp\Output::QuickPrint => 4,
                        Snmp\Output::NumericTimeticks => 5,
                        Snmp\Output::HexText => 6,
                        Snmp\Output::DontPrintUnits => 7,
                        Snmp\Output::ExtendedIndex => 8,
                    };
                }

                function stringOutput(Snmp\StringOutput $format): int {
                    return match ($format) {
                        Snmp\StringOutput::Guess => 1,
                        Snmp\StringOutput::Ascii => 2,
                        Snmp\StringOutput::Hex => 3,
                    };
                }

                $cases = Snmp\Mib::cases();
            ',
            'assertions' => [
                '$cases===' => 'list{enum(Snmp\Mib::AllowUnderscores), enum(Snmp\Mib::CommentTerm), '
                    . 'enum(Snmp\Mib::Replace)}',
            ],
            'ignored_issues' => [],
            'php_version' => '8.6',
        ];

        yield 'php86SodiumXofStatesAvailable' => [
            'code' => '<?php
                function shake128(string $m): string {
                    $s = sodium_crypto_xof_shake128_init();
                    sodium_crypto_xof_shake128_update($s, $m);
                    return sodium_crypto_xof_shake128_squeeze(clone $s, 32);
                }

                function shake256(string $m): string {
                    $s = sodium_crypto_xof_shake256_init();
                    sodium_crypto_xof_shake256_update($s, $m);
                    return sodium_crypto_xof_shake256_squeeze($s, 32);
                }

                function turboshake128(string $m): string {
                    $s = sodium_crypto_xof_turboshake128_init(1);
                    sodium_crypto_xof_turboshake128_update($s, $m);
                    return sodium_crypto_xof_turboshake128_squeeze($s, 32);
                }

                function turboshake256(string $m): string {
                    $s = sodium_crypto_xof_turboshake256_init(null);
                    sodium_crypto_xof_turboshake256_update($s, $m);
                    return sodium_crypto_xof_turboshake256_squeeze($s, 32);
                }

                $state = sodium_crypto_xof_shake128_init();
            ',
            'assertions' => [
                '$state===' => 'SodiumCryptoXofShake128State',
            ],
            'ignored_issues' => [],
            'php_version' => '8.6',
        ];

        yield 'php86GlobalConstantsAreDefined' => [
            'code' => '<?php
                $blockBytes = SODIUM_CRYPTO_XOF_SHAKE128_BLOCKBYTES;
                $filterMode = ARRAY_FILTER_USE_VALUE;
                $addrinfoError = EAI_AGAIN;
            ',
            'assertions' => [
                '$blockBytes===' => '168',
                '$filterMode===' => '0',
                '$addrinfoError===' => 'int',
            ],
            'ignored_issues' => [],
            'php_version' => '8.6',
        ];

        yield 'stubConstantWithAVarDocblockHasTheDocblockType' => [
            'code' => '<?php
                $icuVersion = INTL_ICU_VERSION;
            ',
            'assertions' => [
                '$icuVersion===' => 'string',
            ],
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];

        yield 'php83DateExceptionsAvailable' => [
            'code' => '<?php
                function f(string $s): ?DateTimeImmutable {
                    try {
                        return new DateTimeImmutable($s);
                    } catch (DateMalformedStringException $e) {
                        $exception = $e;
                        return null;
                    }
                }

                function g(DateError $e, DateRangeError $r, DateObjectError $o, DateInvalidTimeZoneException $z): void {}

                function h(
                    DateInvalidOperationException $o,
                    DateMalformedIntervalStringException $i,
                    DateMalformedPeriodStringException $p
                ): void {}
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.3',
        ];

        yield 'php84NewClassesAvailable' => [
            'code' => '<?php
                /** @psalm-pure */
                function money(BcMath\Number $a): string {
                    [$quotient, $remainder] = $a->add("1.5")->mul(2, 2)->divmod(3);

                    return $quotient->round(1, RoundingMode::HalfEven)->value . $remainder->scale;
                }

                /** @psalm-pure */
                function constant_value(ReflectionConstant $c): string {
                    return $c->getName() . $c->getShortName() . ($c->isDeprecated() ? "!" : "");
                }

                function bucket(StreamBucket $b): string {
                    return $b->data . $b->datalen;
                }

                function interval(): Random\IntervalBoundary {
                    return Random\IntervalBoundary::ClosedOpen;
                }

                function f(RequestParseBodyException $e, Dba\Connection $d, Odbc\Connection $c, Odbc\Result $r): void {}

                function hook(): string {
                    return PropertyHookType::Get->value;
                }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.4',
        ];

        yield 'php84PdoDriverSubclassesAvailable' => [
            'code' => '<?php
                /** @psalm-pure */
                function sqlite_flags(): int {
                    return Pdo\Sqlite::OPEN_READONLY | Pdo\Sqlite::OPEN_CREATE;
                }

                /** @psalm-pure */
                function escape(Pdo\Pgsql $db, string $name): string {
                    return $db->escapeIdentifier($name);
                }

                function mysql(Pdo\Mysql $db): int {
                    return $db->getWarningCount() + Pdo\Mysql::ATTR_USE_BUFFERED_QUERY;
                }

                function others(Pdo\Odbc $o, Pdo\Dblib $d, Pdo\Firebird $f): int {
                    return Pdo\Odbc::ATTR_ASSUME_UTF8 + Pdo\Dblib::ATTR_QUERY_TIMEOUT + Pdo\Firebird::getApiVersion();
                }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.4',
        ];

        yield 'php84DomClassesAvailable' => [
            'code' => '<?php
                /** @psalm-capabilities read-props */
                function text(Dom\Text $t, Dom\DocumentType $type): string {
                    $entity = $type->entities->item(0);

                    return $t->substringData(0, 3) . $t->wholeText . $type->name
                        . ($entity instanceof Dom\Entity ? (string) $entity->notationName : "");
                }

                /** @psalm-pure */
                function implementation(Dom\Implementation $i): Dom\HTMLDocument {
                    return $i->createHTMLDocument("title");
                }

                function nodes(
                    Dom\CDATASection $c,
                    Dom\ProcessingInstruction $p,
                    Dom\Comment $comment,
                    Dom\EntityReference $r,
                    Dom\Notation $n,
                    Dom\NamespaceInfo $info
                ): string {
                    return $p->target . $n->systemId . (string) $info->prefix . Dom\AdjacentPosition::AfterEnd->value;
                }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.4',
        ];

        yield 'php85UriAvailable' => [
            'code' => '<?php
                /** @psalm-pure */
                function rfc3986(string $s): ?string {
                    $uri = Uri\Rfc3986\Uri::parse($s);
                    if ($uri === null) {
                        return null;
                    }

                    $other = $uri->withPath("/a")->withQuery("b=c");

                    return $other->equals($uri, Uri\UriComparisonMode::IncludeFragment) ? $uri->getHost() : $other->toString();
                }

                /** @psalm-pure */
                function whatwg(string $s): string {
                    $url = new Uri\WhatWg\Url($s, null, $softErrors);
                    foreach ($softErrors as $error) {
                        if ($error->type === Uri\WhatWg\UrlValidationErrorType::PortInvalid) {
                            return $error->context;
                        }
                    }

                    return $url->toAsciiString();
                }

                function invalid(Uri\WhatWg\InvalidUrlException $e, Uri\UriError $error, Uri\UriException $base): array {
                    return $e->errors;
                }

                function filter(Filter\FilterFailedException $e): Filter\FilterException {
                    return $e;
                }

                function format(IntlListFormatter $formatter): string|false {
                    return $formatter->format(["a", "b"]);
                }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        yield 'classBelowItsVersion' => [
            'code' => '<?php
                $m = new WeakMap();
            ',
            'error_message' => 'UndefinedClass - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:26 - WeakMap is not'
                . ' defined for the analysed PHP version 7.4 (it was introduced in PHP 8.0);'
                . ' define a polyfill (symfony/polyfill-php80 ships some of the symbols of that version), or raise'
                . ' the analysed PHP version',
            'error_levels' => [],
            'php_version' => '7.4',
        ];

        yield 'docblockClassBelowItsVersion' => [
            'code' => '<?php
                /** @param WeakMap<object, int> $m */
                function f($m): void {}
            ',
            'error_message' => 'UndefinedDocblockClass',
            'error_levels' => [],
            'php_version' => '7.4',
        ];

        yield 'interfaceBelowItsVersion' => [
            'code' => '<?php
                final class A implements Stringable {
                    public function __toString(): string { return ""; }
                }
            ',
            'error_message' => 'UndefinedClass',
            'error_levels' => [],
            'php_version' => '7.4',
        ];

        yield 'propertyInheritsItsClassVersion' => [
            'code' => '<?php
                /** @psalm-suppress UndefinedClass */
                function f(UnitEnum $e): string {
                    return $e->name;
                }
            ',
            'error_message' => 'UndefinedPropertyFetch',
            'error_levels' => [],
            'php_version' => '8.0',
        ];

        yield 'attributeBelowItsVersion' => [
            'code' => '<?php
                // No polyfill and analysing below 8.5, so #[\NoDiscard] is not available: Psalm
                // reports it as an undefined attribute class (enforcement is orthogonal).
                #[\NoDiscard]
                function f(): int { return 1; }

                $x = f();
                echo $x;
            ',
            'error_message' => 'UndefinedAttributeClass',
            'error_levels' => [],
            'php_version' => '8.0',
        ];

        yield 'guardedForAnOlderVersion' => [
            'code' => '<?php
                function a(): ?object {
                    if (PHP_VERSION_ID < 80000) {
                        return new WeakMap();
                    }

                    return null;
                }
            ',
            'error_message' => 'UndefinedClass',
            'error_levels' => [],
            'php_version' => '7.4',
        ];

        yield 'callMapFunctionBelowItsVersion' => [
            'code' => '<?php
                $x = fdiv(1.0, 2.0);
            ',
            'error_message' => 'Function fdiv is not defined for the analysed PHP version 7.4'
                . ' (it was introduced in PHP 8.0',
            'error_levels' => [],
            'php_version' => '7.4',
        ];

        yield 'callMapMethodBelowItsVersion' => [
            'code' => '<?php
                function f(ReflectionClass $r): bool {
                    return $r->isEnum();
                }
            ',
            'error_message' => 'Method ReflectionClass::isEnum is not defined for the analysed PHP version 8.0'
                . ' (it was introduced in PHP 8.1',
            'error_levels' => [],
            'php_version' => '8.0',
        ];

        yield 'php86DurationBelowItsVersion' => [
            'code' => '<?php
                function f(Time\Duration $d): void {}
            ',
            'error_message' => 'UndefinedClass',
            'error_levels' => [],
            'php_version' => '8.5',
        ];

        yield 'php86PollApiBelowItsVersion' => [
            'code' => '<?php
                $context = new Io\Poll\Context();
            ',
            'error_message' => 'UndefinedClass',
            'error_levels' => [],
            'php_version' => '8.5',
        ];

        yield 'php86DurationPropertyIsReadonly' => [
            'code' => '<?php
                function f(Time\Duration $d): void {
                    $d->seconds = 1;
                }
            ',
            'error_message' => 'InaccessibleProperty',
            'error_levels' => [],
            'php_version' => '8.6',
        ];

        yield 'php86DurationNotInstantiable' => [
            'code' => '<?php
                $d = new Time\Duration();
            ',
            'error_message' => 'InaccessibleMethod',
            'error_levels' => [],
            'php_version' => '8.6',
        ];

        yield 'php86SortDirectionNonExhaustiveMatch' => [
            'code' => '<?php
                function f(SortDirection $d): string {
                    return match ($d) {
                        SortDirection::Ascending => "asc",
                    };
                }
            ',
            'error_message' => 'UnhandledMatchCondition',
            'error_levels' => [],
            'php_version' => '8.6',
        ];

        $classesNewIn86 = [
            'StreamError' => 'StreamError',
            'StreamException' => 'StreamException',
            'UriBuilder' => 'Uri\Rfc3986\UriBuilder',
            'UrlBuilder' => 'Uri\WhatWg\UrlBuilder',
            'OpensslSession' => 'Openssl\Session',
            'OpensslPsk' => 'Openssl\Psk',
            'OpensslException' => 'Openssl\OpensslException',
            'IntlNumberRangeFormatter' => 'IntlNumberRangeFormatter',
            'SodiumCryptoXofShake128State' => 'SodiumCryptoXofShake128State',
            'SodiumCryptoXofShake256State' => 'SodiumCryptoXofShake256State',
            'SodiumCryptoXofTurboshake128State' => 'SodiumCryptoXofTurboshake128State',
            'SodiumCryptoXofTurboshake256State' => 'SodiumCryptoXofTurboshake256State',
        ];

        foreach ($classesNewIn86 as $case => $class) {
            yield 'php86' . $case . 'BelowItsVersion' => [
                'code' => '<?php
                    function f(' . $class . ' $c): void {}
                ',
                'error_message' => $class . ' is not defined for the analysed PHP version 8.5'
                    . ' (it was introduced in PHP 8.6',
                'error_levels' => [],
                'php_version' => '8.5',
            ];
        }

        $classesNewBefore86 = [
            '8.3' => [
                'DateError', 'DateObjectError', 'DateRangeError', 'DateException', 'DateInvalidOperationException',
                'DateInvalidTimeZoneException', 'DateMalformedIntervalStringException',
                'DateMalformedPeriodStringException', 'DateMalformedStringException', 'Random\IntervalBoundary',
            ],
            '8.4' => [
                'RequestParseBodyException', 'BcMath\Number', 'Dba\Connection', 'Dom\Implementation',
                'Dom\DtdNamedNodeMap', 'Dom\AdjacentPosition', 'Dom\CharacterData', 'Dom\Text', 'Dom\CDATASection',
                'Dom\ProcessingInstruction', 'Dom\Comment', 'Dom\DocumentType', 'Dom\Entity', 'Dom\EntityReference',
                'Dom\Notation', 'Dom\NamespaceInfo', 'Odbc\Connection', 'Odbc\Result', 'Pcntl\QosClass', 'Pdo\Dblib',
                'Pdo\Firebird', 'Pdo\Mysql', 'Pdo\Odbc', 'Pdo\Pgsql', 'Pdo\Sqlite', 'ReflectionConstant', 'Soap\Url',
                'Soap\Sdl', 'RoundingMode', 'StreamBucket', 'PropertyHookType',
            ],
            '8.5' => [
                'Uri\UriException', 'Uri\UriError', 'Uri\InvalidUriException', 'Uri\UriComparisonMode',
                'Uri\Rfc3986\Uri', 'Uri\WhatWg\InvalidUrlException', 'Uri\WhatWg\UrlValidationErrorType',
                'Uri\WhatWg\UrlValidationError', 'Uri\WhatWg\Url', 'Filter\FilterException',
                'Filter\FilterFailedException', 'IntlListFormatter',
            ],
        ];

        $previous_versions = ['8.3' => '8.2', '8.4' => '8.3', '8.5' => '8.4'];
        foreach ($classesNewBefore86 as $version => $classes) {
            foreach ($classes as $class) {
                yield 'php' . str_replace('.', '', $version) . str_replace('\\', '', $class) . 'BelowItsVersion' => [
                    'code' => '<?php
                        function f(' . $class . ' $c): void {}
                    ',
                    'error_message' => $class . ' is not defined for the analysed PHP version '
                        . $previous_versions[$version] . ' (it was introduced in PHP ' . $version,
                    'error_levels' => [],
                    'php_version' => $previous_versions[$version],
                ];
            }
        }

        yield 'php85IntlListFormatterFormatRecordsTheError' => [
            'code' => '<?php
                /** @psalm-pure */
                function format(IntlListFormatter $formatter): string|false {
                    return $formatter->format(["a", "b"]);
                }
            ',
            'error_message' => 'ImpureMethodCall',
            'error_levels' => [],
            'php_version' => '8.5',
        ];

        yield 'php84DomNodeRemovalWritesTheParent' => [
            'code' => '<?php
                /** @psalm-pure */
                function remove(Dom\Text $t): Dom\Text {
                    $t->remove();

                    return $t;
                }
            ',
            'error_message' => 'ImpureMethodCall',
            'error_levels' => [],
            'php_version' => '8.4',
        ];

        yield 'php84PgsqlCopyIsIo' => [
            'code' => '<?php
                /** @psalm-pure */
                function rows(Pdo\Pgsql $db): array|false {
                    return $db->copyToArray("t");
                }
            ',
            'error_message' => 'ImpureMethodCall',
            'error_levels' => [],
            'php_version' => '8.4',
        ];

        yield 'php86OpensslSessionIdIsReadonly' => [
            'code' => '<?php
                function f(Openssl\Session $s): void {
                    $s->id = "other";
                }
            ',
            'error_message' => 'InaccessibleProperty',
            'error_levels' => [],
            'php_version' => '8.6',
        ];

        yield 'php86StreamErrorIsImmutable' => [
            'code' => '<?php
                function f(StreamError $e): void {
                    $e->severity = 1;
                }
            ',
            'error_message' => 'InaccessibleProperty',
            'error_levels' => [],
            'php_version' => '8.6',
        ];

        yield 'php86IntlNumberRangeFormatterNotInstantiable' => [
            'code' => '<?php
                $f = new IntlNumberRangeFormatter();
            ',
            'error_message' => 'InaccessibleMethod',
            'error_levels' => [],
            'php_version' => '8.6',
        ];

        yield 'php86SodiumXofStateNotInstantiable' => [
            'code' => '<?php
                $s = new SodiumCryptoXofShake128State();
            ',
            'error_message' => 'InaccessibleMethod',
            'error_levels' => [],
            'php_version' => '8.6',
        ];

        yield 'php86SodiumXofStatesAreDistinct' => [
            'code' => '<?php
                function f(SodiumCryptoXofShake256State $s): string {
                    return sodium_crypto_xof_shake128_squeeze($s, 32);
                }
            ',
            'error_message' => 'InvalidArgument',
            'error_levels' => [],
            'php_version' => '8.6',
        ];
    }
}
