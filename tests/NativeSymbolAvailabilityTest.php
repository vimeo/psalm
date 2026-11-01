<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Context;
use Psalm\IssueBuffer;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function array_filter;

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
                . ' install symfony/polyfill-php80',
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
    }
}
