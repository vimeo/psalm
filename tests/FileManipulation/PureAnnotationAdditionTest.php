<?php

declare(strict_types=1);

namespace Psalm\Tests\FileManipulation;

use Override;

final class PureAnnotationAdditionTest extends FileManipulationTestCase
{
    /**
     * @psalm-pure
     */
    #[Override]
    public function providerValidCodeParse(): array
    {
        return [
            'correctClassCasing' => [
                'input' => '<?php
                    interface F {
                        /**
                         * @return static
                         * @psalm-mutation-free
                         */
                        public function m(): self;
                    }

                    abstract class G implements F {}

                    class H extends G {
                        public function m(): F {
                            return $this;
                        }
                    }',
                'output' => '<?php
                    interface F {
                        /**
                         * @return static
                         * @psalm-mutation-free
                         */
                        public function m(): self;
                    }

                    abstract class G implements F {}

                    class H extends G {
                        /**
                         * @psalm-capabilities read-props
                         */
                        public function m(): F {
                            return $this;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'keepExplicitImpureAnnotation' => [
                'input' => '<?php
                    class Hook {
                        /** @psalm-impure */
                        public function prepare(): int {
                            return 1;
                        }
                    }',
                'output' => '<?php
                    class Hook {
                        /** @psalm-impure */
                        public function prepare(): int {
                            return 1;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'keepExplicitAnnotationOfOverriddenMethod' => [
                'input' => '<?php
                    class Base {
                        /** @psalm-capabilities read-props|write-this-props */
                        public function items(): array {
                            return [];
                        }
                    }

                    final class Child extends Base {
                        private array $cache = [];

                        /** @psalm-capabilities read-props|write-this-props */
                        public function items(): array {
                            $this->cache = [1];
                            return $this->cache;
                        }
                    }',
                'output' => '<?php
                    class Base {
                        /** @psalm-capabilities read-props|write-this-props */
                        public function items(): array {
                            return [];
                        }
                    }

                    final class Child extends Base {
                        private array $cache = [];

                        /** @psalm-capabilities read-props|write-this-props */
                        public function items(): array {
                            $this->cache = [1];
                            return $this->cache;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddPureAnnotationWhenParamDefaultIsImpure' => [
                'input' => '<?php
                    final class Bus {
                        public function __construct() {
                            echo "created";
                        }
                    }

                    function useDefault(Bus $bus = new Bus()): Bus {
                        return $bus;
                    }',
                'output' => '<?php
                    final class Bus {
                        public function __construct() {
                            echo "created";
                        }
                    }

                    function useDefault(Bus $bus = new Bus()): Bus {
                        return $bus;
                    }',
                'php_version' => '8.1',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'addPureAnnotationWhenParamDefaultIsPure' => [
                'input' => '<?php
                    final class Options {
                        /**
                         * @psalm-pure
                         */
                        public function __construct(public int $limit = 10) {}
                    }

                    function useDefault(Options $options = new Options()): int {
                        return $options->limit;
                    }',
                'output' => '<?php
                    final class Options {
                        /**
                         * @psalm-pure
                         */
                        public function __construct(public int $limit = 10) {}
                    }

                    /**
                     * @psalm-capabilities read-props
                     */
                    function useDefault(Options $options = new Options()): int {
                        return $options->limit;
                    }',
                'php_version' => '8.1',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddPureAnnotationWhenParamDefaultCallsImpureDefault' => [
                'input' => '<?php
                    function useDefault(Bus $bus = new Bus()): Bus {
                        return $bus;
                    }

                    final class Bus {
                        public function __construct(?Logger $logger = new Logger()) {}
                    }

                    final class Logger {
                        public function __construct() {
                            echo "created";
                        }
                    }',
                'output' => '<?php
                    function useDefault(Bus $bus = new Bus()): Bus {
                        return $bus;
                    }

                    final class Bus {
                        public function __construct(?Logger $logger = new Logger()) {}
                    }

                    final class Logger {
                        public function __construct() {
                            echo "created";
                        }
                    }',
                'php_version' => '8.1',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'addPureAnnotationWhenParamDefaultIsAnnotatedClosure' => [
                'input' => '<?php
                    function useDefault(Closure $log = /** @psalm-impure */ static function (): int {
                        echo "log";
                        return 1;
                    }): Closure {
                        return $log;
                    }',
                'output' => '<?php
                    /**
                     * @psalm-pure
                     */
                    function useDefault(Closure $log = /** @psalm-impure */ static function (): int {
                        echo "log";
                        return 1;
                    }): Closure {
                        return $log;
                    }',
                'php_version' => '8.5',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddPureAnnotationToOverriddenMethodOrItsCallers' => [
                'input' => '<?php
                    abstract class Base {
                        public function hook(): int {
                            return 1;
                        }

                        final public function run(): int {
                            return $this->hook();
                        }
                    }

                    final class Child extends Base {
                        public function hook(): int {
                            echo "hook";
                            return 2;
                        }
                    }',
                'output' => '<?php
                    abstract class Base {
                        public function hook(): int {
                            return 1;
                        }

                        final public function run(): int {
                            return $this->hook();
                        }
                    }

                    final class Child extends Base {
                        public function hook(): int {
                            echo "hook";
                            return 2;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontSuggestWhatAClassBoundPurityTemplateExceeds' => [
                'input' => '<?php
                    /**
                     * @psalm-purity-template P
                     */
                    abstract class Base {
                        /**
                         * @psalm-capabilities read-props
                         * @psalm-purity-from-template P
                         */
                        public function limit(): int {
                            return 1;
                        }
                    }

                    final class Db extends Base {
                        public function limit(): int {
                            return parent::limit() + 1;
                        }
                    }',
                'output' => '<?php
                    /**
                     * @psalm-purity-template P
                     */
                    abstract class Base {
                        /**
                         * @psalm-capabilities read-props
                         * @psalm-purity-from-template P
                         */
                        public function limit(): int {
                            return 1;
                        }
                    }

                    final class Db extends Base {
                        public function limit(): int {
                            return parent::limit() + 1;
                        }
                    }',
                'php_version' => '8.1',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'addPureAnnotationToFunction' => [
                'input' => '<?php
                    function foo(string $s): string {
                        return $s;
                    }',
                'output' => '<?php
                    /**
                     * @psalm-pure
                     */
                    function foo(string $s): string {
                        return $s;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'addPureAnnotationToFunctionCreatingArrowFunction' => [
                'input' => '<?php
                    /**
                     * @param Closure[_](int): string $f
                     */
                    function foo(Closure $f): Closure {
                        return fn(int $i): string => $f($i);
                    }',
                'output' => '<?php
                    /**
                     * @param Closure[_](int): string $f
                     *
                     * @psalm-pure
                     */
                    function foo(Closure $f): Closure {
                        return fn(int $i): string => $f($i);
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'selfCall' => [
                'input' => '<?php
                    function foo(string $s, int $v): string {
                        if ($v > 5) {
                            return foo($s, $v - 1);
                        }
                        return $s;
                    }',
                'output' => '<?php
                    /**
                     * @psalm-pure
                     */
                    function foo(string $s, int $v): string {
                        if ($v > 5) {
                            return foo($s, $v - 1);
                        }
                        return $s;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'callsPureFunctionDeclaredLater' => [
                'input' => '<?php
                    function foo(string $s): string {
                        return bar($s) . "!";
                    }
                    function bar(string $s): string {
                        return $s;
                    }',
                'output' => '<?php
                    /**
                     * @psalm-pure
                     */
                    function foo(string $s): string {
                        return bar($s) . "!";
                    }
                    /**
                     * @psalm-pure
                     */
                    function bar(string $s): string {
                        return $s;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddPureWhenCalleeDeclaredLaterIsImpure' => [
                'input' => '<?php
                    function foo(string $s): string {
                        return bar($s) . "!";
                    }
                    function bar(string $s): string {
                        echo $s;
                        return $s;
                    }',
                'output' => '<?php
                    function foo(string $s): string {
                        return bar($s) . "!";
                    }
                    function bar(string $s): string {
                        echo $s;
                        return $s;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddPureWhenIndirectCalleeIsImpure' => [
                'input' => '<?php
                    function a(string $s): string {
                        return b($s);
                    }
                    function b(string $s): string {
                        return c($s);
                    }
                    function c(string $s): string {
                        return (string) file_get_contents($s);
                    }',
                'output' => '<?php
                    function a(string $s): string {
                        return b($s);
                    }
                    function b(string $s): string {
                        return c($s);
                    }
                    function c(string $s): string {
                        return (string) file_get_contents($s);
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'staticMethodCallsPureStaticMethodDeclaredLater' => [
                'input' => '<?php
                    final class A {
                        public static function foo(string $s): string {
                            return self::bar($s) . "!";
                        }
                        public static function bar(string $s): string {
                            return $s;
                        }
                    }',
                'output' => '<?php
                    final class A {
                        /**
                         * @psalm-pure
                         */
                        public static function foo(string $s): string {
                            return self::bar($s) . "!";
                        }
                        /**
                         * @psalm-pure
                         */
                        public static function bar(string $s): string {
                            return $s;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'methodCallsPureMethodOfFinalClassDeclaredLater' => [
                'input' => '<?php
                    final class B {
                        public function bar(string $s): string {
                            return $s;
                        }
                    }
                    final class A {
                        public function foo(B $b, string $s): string {
                            return $b->bar($s) . "!";
                        }
                    }',
                'output' => '<?php
                    final class B {
                        /**
                         * @psalm-pure
                         */
                        public function bar(string $s): string {
                            return $s;
                        }
                    }
                    final class A {
                        /**
                         * @psalm-pure
                         */
                        public function foo(B $b, string $s): string {
                            return $b->bar($s) . "!";
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddPureWhenCalleeCanBeOverridden' => [
                'input' => '<?php
                    class B {
                        public function bar(string $s): string {
                            return $s;
                        }
                    }
                    final class A {
                        public function foo(B $b, string $s): string {
                            return $b->bar($s) . "!";
                        }
                    }',
                'output' => '<?php
                    class B {
                        /**
                         * @psalm-pure
                         */
                        public function bar(string $s): string {
                            return $s;
                        }
                    }
                    final class A {
                        public function foo(B $b, string $s): string {
                            return $b->bar($s) . "!";
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'selfStaticMethodCallIndirect' => [
                'input' => '<?php
                    final class A {
                        public static function foo(string $s, int $v): string {
                            if ($v > 5) {
                                return self::bar($s, $v - 1);
                            }
                            return $s;
                        }
                        public static function bar(string $s, int $v): string {
                            return self::foo($s, $v);
                        }
                    }',
                'output' => '<?php
                    final class A {
                        /**
                         * @psalm-pure
                         */
                        public static function foo(string $s, int $v): string {
                            if ($v > 5) {
                                return self::bar($s, $v - 1);
                            }
                            return $s;
                        }
                        /**
                         * @psalm-pure
                         */
                        public static function bar(string $s, int $v): string {
                            return self::foo($s, $v);
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'selfCallIndirectWithImpureBase' => [
                'input' => '<?php
                    function foo(string $s, int $v): string {
                        if ($v > 5) {
                            return bar($s, $v - 1);
                        }
                        echo $s;
                        return $s;
                    }
                    function bar(string $s, int $v): string {
                        return foo($s, $v);
                    }',
                'output' => '<?php
                    function foo(string $s, int $v): string {
                        if ($v > 5) {
                            return bar($s, $v - 1);
                        }
                        echo $s;
                        return $s;
                    }
                    function bar(string $s, int $v): string {
                        return foo($s, $v);
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'closureCallsPureFunctionDeclaredLater' => [
                'input' => '<?php
                    $f = function(string $s): string {
                        return bar($s);
                    };
                    function bar(string $s): string {
                        return $s;
                    }',
                'output' => '<?php
                    $f = /**
                     * @psalm-pure
                     */
                    function(string $s): string {
                        return bar($s);
                    };
                    /**
                     * @psalm-pure
                     */
                    function bar(string $s): string {
                        return $s;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'closuresOnTheSameLineAreBothAnnotated' => [
                'input' => '<?php
                    $a = function(): int { return 1; }; $b = function(): int { return 2; };',
                'output' => '<?php
                    $a = /**
                     * @psalm-pure
                     */
                    function(): int { return 1; }; $b = /**
                     * @psalm-pure
                     */
                    function(): int { return 2; };',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'selfCallIndirect' => [
                'input' => '<?php
                    function foo(string $s, int $v): string {
                        if ($v > 5) {
                            return bar($s, $v - 1);
                        }
                        return $s;
                    }
                    function bar(string $s, int $v): string {
                        return foo($s, $v);
                    }',
                'output' => '<?php
                    /**
                     * @psalm-pure
                     */
                    function foo(string $s, int $v): string {
                        if ($v > 5) {
                            return bar($s, $v - 1);
                        }
                        return $s;
                    }
                    /**
                     * @psalm-pure
                     */
                    function bar(string $s, int $v): string {
                        return foo($s, $v);
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'selfMethodCall' => [
                'input' => '<?php
                    class A {
                        public function foo(string $s, int $v): string {
                            if ($v > 5) {
                                return $this->foo($s, $v - 1);
                            }
                            return $s;
                        }
                    }',
                'output' => '<?php
                    class A {
                        /**
                         * @psalm-capabilities read-props
                         */
                        public function foo(string $s, int $v): string {
                            if ($v > 5) {
                                return $this->foo($s, $v - 1);
                            }
                            return $s;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'selfMethodCall2' => [
                'input' => '<?php
                    class A {
                        public A $a;
                        public function foo(int $ex): int {
                            if ($ex === 0) {
                                return $ex;
                            }
                            return $this->a->foo($ex - 1);
                        }
                    }
                    class B extends A {
                        public function foo(int $ex): int {
                            echo "test";
                            if ($ex === 0) {
                                return $ex;
                            }
                            return $this->a->foo($ex - 1);
                        }
                    }',
                'output' => '<?php
                    class A {
                        public A $a;
                        public function foo(int $ex): int {
                            if ($ex === 0) {
                                return $ex;
                            }
                            return $this->a->foo($ex - 1);
                        }
                    }
                    class B extends A {
                        public function foo(int $ex): int {
                            echo "test";
                            if ($ex === 0) {
                                return $ex;
                            }
                            return $this->a->foo($ex - 1);
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'selfMethodCall3' => [
                'input' => '<?php
                    class A {
                        public function foo(int $ex): int {
                            if ($ex === 0) {
                                return $ex;
                            }
                            return $this->foo($ex - 1);
                        }
                    }
                    class B extends A {
                        public function foo(int $ex): int {
                            echo "test";
                            if ($ex === 0) {
                                return $ex;
                            }
                            return $this->foo($ex - 1);
                        }
                    }',
                'output' => '<?php
                    class A {
                        public function foo(int $ex): int {
                            if ($ex === 0) {
                                return $ex;
                            }
                            return $this->foo($ex - 1);
                        }
                    }
                    class B extends A {
                        public function foo(int $ex): int {
                            echo "test";
                            if ($ex === 0) {
                                return $ex;
                            }
                            return $this->foo($ex - 1);
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'selfStaticMethodCall' => [
                'input' => '<?php
                    class A {
                        public static function foo(string $s, int $v): string {
                            if ($v > 5) {
                                return self::foo($s, $v - 1);
                            }
                            return $s;
                        }
                    }',
                'output' => '<?php
                    class A {
                        /**
                         * @psalm-pure
                         */
                        public static function foo(string $s, int $v): string {
                            if ($v > 5) {
                                return self::foo($s, $v - 1);
                            }
                            return $s;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'selfClosureCall' => [
                'input' => '<?php
                    $f = function(string $s, int $v) use (&$f): string {
                        if ($v > 5) {
                            return $f($s, $v - 1);
                        }
                        return $s;
                    }',
                'output' => '<?php
                    $f = /**
                     * @psalm-pure
                     */
                    function(string $s, int $v) use (&$f): string {
                        if ($v > 5) {
                            return $f($s, $v - 1);
                        }
                        return $s;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'propertySetIsNotMutationFree' => [
                'input' => '<?php
                    class A {
                        /**
                         * @psalm-readonly-allow-private-mutation
                         * @var list<FunctionLikeParameter>
                         */
                        public array $params = [];

                        /**
                         * @psalm-readonly-allow-private-mutation
                         * @var array<string, bool>
                         */
                        public array $param_lookup = [];

                        /**
                         * @internal
                         */
                        public function addParam(FunctionLikeParameter $param, ?bool $lookup_value = null): void
                        {
                            $this->params[] = $param;
                            $this->param_lookup[$param->name] = $lookup_value ?? true;
                        }
                    }',
                'output' => '<?php
                    class A {
                        /**
                         * @psalm-readonly-allow-private-mutation
                         * @var list<FunctionLikeParameter>
                         */
                        public array $params = [];

                        /**
                         * @psalm-readonly-allow-private-mutation
                         * @var array<string, bool>
                         */
                        public array $param_lookup = [];

                        /**
                         * @internal
                         *
                         * @psalm-capabilities read-props|write-this-props|write-refs
                         */
                        public function addParam(FunctionLikeParameter $param, ?bool $lookup_value = null): void
                        {
                            $this->params[] = $param;
                            $this->param_lookup[$param->name] = $lookup_value ?? true;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'addPureAnnotationToFunctionWithExistingDocblock' => [
                'input' => '<?php
                    /**
                     * @return string
                     */
                    function foo(string $s) {
                        return $s;
                    }',
                'output' => '<?php
                    /**
                     * @return string
                     *
                     * @psalm-pure
                     */
                    function foo(string $s) {
                        return $s;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddPureAnnotationToImpureFunction' => [
                'input' => '<?php
                    function foo(string $s): string {
                        echo $s;
                        return $s;
                    }',
                'output' => '<?php
                    function foo(string $s): string {
                        echo $s;
                        return $s;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddPureAnnotationToFunctionReadingTheClock' => [
                'input' => '<?php
                    function year(): string {
                        return date("Y");
                    }

                    function yearOf(int $ts): string {
                        return date("Y", $ts);
                    }',
                'output' => '<?php
                    function year(): string {
                        return date("Y");
                    }

                    /**
                     * @psalm-pure
                     */
                    function yearOf(int $ts): string {
                        return date("Y", $ts);
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddPureAnnotationToMutationFreeMethod' => [
                'input' => '<?php
                    class A {
                        public string $foo = "hello";

                        public function getFoo() : string {
                            return $this->foo;
                        }
                    }',
                'output' => '<?php
                    class A {
                        public string $foo = "hello";

                        public function getFoo() : string {
                            return $this->foo;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddPureAnnotationToFunctionWithImpureCall' => [
                'input' => '<?php
                    function foo(string $s): string {
                        if (file_exists($s)) {
                            return "";
                        }
                        return $s;
                    }',
                'output' => '<?php
                    function foo(string $s): string {
                        if (file_exists($s)) {
                            return "";
                        }
                        return $s;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddPureAnnotationToFunctionWithImpureClosure' => [
                'input' => '<?php
                    /** @param list<string> $arr */
                    function foo(array $arr): array {
                        return array_map($arr, function ($s) { echo $s; return $s;});
                    }',
                'output' => '<?php
                    /** @param list<string> $arr */
                    function foo(array $arr): array {
                        return array_map($arr, function ($s) { echo $s; return $s;});
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddWhenReferencingThis' => [
                'input' => '<?php
                    abstract class A {
                        public int $a = 5;

                        public function foo() : self {
                            return $this;
                        }
                    }

                    class B extends A {}',
                'output' => '<?php
                    abstract class A {
                        public int $a = 5;

                        /**
                         * @psalm-capabilities read-props
                         */
                        public function foo() : self {
                            return $this;
                        }
                    }

                    class B extends A {}',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddInChildMethod' => [
                'input' => '<?php
                    class A {
                        public int $a = 5;

                        public function foo(string $s) : string {
                            echo "test";
                            return $string . $this->a;
                        }
                    }

                    class B extends A {
                        public function foo(string $s) : string {
                            return $string;
                        }
                    }',
                'output' => '<?php
                    class A {
                        public int $a = 5;

                        public function foo(string $s) : string {
                            echo "test";
                            return $string . $this->a;
                        }
                    }

                    class B extends A {
                        /**
                         * @psalm-pure
                         */
                        public function foo(string $s) : string {
                            return $string;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'doAddInOtherMethod' => [
                'input' => '<?php
                    class A {
                        public int $a = 5;

                        public function foo(string $s) : string {
                            return $string . $this->a;
                        }
                    }

                    class B extends A {
                        public function bar(string $s) : string {
                            return $string;
                        }
                    }',
                'output' => '<?php
                    class A {
                        public int $a = 5;

                        /**
                         * @psalm-capabilities read-props
                         */
                        public function foo(string $s) : string {
                            return $string . $this->a;
                        }
                    }

                    class B extends A {
                        /**
                         * @psalm-pure
                         */
                        public function bar(string $s) : string {
                            return $string;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddPureIfCallableNotPure' => [
                'input' => '<?php
                    function pure(callable $callable): string{
                        return $callable();
                    }',
                'output' => '<?php
                    function pure(callable $callable): string{
                        return $callable();
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
        ];
    }
}
