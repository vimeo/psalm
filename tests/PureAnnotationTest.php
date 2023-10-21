<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class PureAnnotationTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'simplePureFunction' => [
                'code' => '<?php
                    namespace Bar;

                    /** @psalm-pure */
                    function filterOdd(int $i) : ?int {
                        if ($i % 2 === 0) {
                            return $i;
                        }

                        return null;
                    }',
            ],
            'pureFunctionCallingBuiltinFunctions' => [
                'code' => '<?php
                    namespace Bar;

                    /** @psalm-pure */
                    function lower(string $s) : string {
                        return substr(strtolower($s), 0, 10);
                    }',
            ],
            'pureWithStrReplace' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function highlight(string $needle, string $output) : string {
                        $needle = preg_quote($needle, \'#\');
                        $needles = str_replace([\'"\', \' \'], [\'\', \'|\'], $needle);
                        $output = (string) preg_replace("#({$needles})#im", "<mark>$1</mark>", $output);

                        return $output;
                    }',
            ],
            'implicitAnnotations' => [
                'code' => '<?php
                    abstract class Foo {
                        private array $options;
                        private array $defaultOptions;

                        function __construct(array $options) {
                            $this->setOptions($options);
                            $this->setDefaultOptions($this->getOptions());
                        }

                        function getOptions(): array {
                            return $this->options;
                        }

                        public final function setOptions(array $options): void {
                            $this->options = $options;
                        }

                        public final function setDefaultOptions(array $defaultOptions): void {
                            $this->defaultOptions = $defaultOptions;
                        }
                    }',
            ],
            'canCreateObjectWithNoExternalMutations' => [
                'code' => '<?php
                    /** @psalm-external-mutation-free */
                    class Counter {
                        private int $count = 0;

                        public function __construct(int $count) {
                            $this->count = $count;
                        }

                        public function increment() : void {
                            $this->count++;
                        }

                        public function incrementByTwo() : void {
                            $this->count = $this->count + 2;
                        }

                        public function incrementByFive() : void {
                            $this->count += 5;
                        }
                    }

                    /** @psalm-pure */
                    function makesACounter(int $i) : Counter {
                        $c = new Counter($i);
                        $c->increment();
                        $c->incrementByTwo();
                        $c->incrementByFive();
                        return $c;
                    }',
            ],
            'canCreateImmutableObject' => [
                'code' => '<?php
                    /** @psalm-immutable */
                    class A {
                        private string $s;

                        public function __construct(string $s) {
                            $this->s = $s;
                        }

                        public function getShort() : string {
                            return substr($this->s, 0, 5);
                        }
                    }

                    /** @psalm-pure */
                    function makeA(string $s) : A {
                        $a = new A($s);

                        if ($a->getShort() === "bar") {
                            return new A("foo");
                        }

                        return $a;
                    }',
            ],
            'assertIsPureInProductionn' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     */
                    function toDateTime(?DateTime $dateTime) : DateTime {
                        assert($dateTime instanceof DateTime);
                        return $dateTime;
                    }',
            ],
            'allowArrayMapClosure' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @param string[] $arr
                     */
                    function foo(array $arr) : array {
                        return \array_map(function(string $s) { return $s;}, $arr);
                    }',
            ],
            'pureBuiltinCall' => [
                'code' => '<?php
                    final class Date
                    {
                        /**
                         * @param non-empty-string $tzString
                         * @psalm-pure
                         */
                        public static function timeZone(string $tzString) : DateTimeZone
                        {
                            return new \DateTimeZone($tzString);
                        }
                    }',
            ],
            'sortFunctionPure' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     *
                     * @param int[] $ar
                     */
                    function foo(array $ar): int
                    {
                        usort($ar, static function (int $a, int $b): int {
                            return $a <=> $b;
                        });

                        return $ar[0] ?? 0;
                    }',
            ],
            'exitFunctionWithNoArgumentIsPure' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function foo(): void {
                        exit;
                    }
                ',
            ],
            'exitFunctionWithIntegerArgumentIsPure' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function foo(): void {
                        exit(0);
                    }
                ',
            ],
            'dieFunctionWithNoArgumentIsPure' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function foo(): void {
                        die;
                    }
                ',
            ],
            'dieFunctionWithIntegerArgumentIsPure' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function foo(): void {
                        die(0);
                    }
                ',
            ],
            'allowPureToString' => [
                'code' => '<?php
                    class A {
                        /** @psalm-pure */
                        public function __toString() {
                            return "bar";
                        }
                    }

                    /**
                     * @psalm-pure
                     */
                    function foo(string $s, A $a) : string {
                        if ($a == $s) {}
                        return $s;
                    }',
            ],
            'exceptionGetMessage' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     */
                    function getMessage(Throwable $e): string {
                        return $e->getMessage();
                    }

                    echo getMessage(new Exception("test"));',
            ],
            'exceptionGetCode' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     *
                     * @return int|string https://www.php.net/manual/en/throwable.getcode.php
                     */
                    function getCode(Throwable $e) {
                        return $e->getCode();
                    }

                    echo getCode(new Exception("test"));',
            ],
            'exceptionGetFile' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     */
                    function getFile(Throwable $e): string {
                        return $e->getFile();
                    }

                    echo getFile(new Exception("test"));',
            ],
            'exceptionGetLine' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     */
                    function getLine(Throwable $e): int {
                        return $e->getLine();
                    }

                    echo getLine(new Exception("test"));',
            ],
            'exceptionGetTrace' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     */
                    function getTrace(Throwable $e): array {
                        return $e->getTrace();
                    }

                    echo count(getTrace(new Exception("test")));',
            ],
            'exceptionGetPrevious' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     */
                    function getPrevious(Throwable $e): ?Throwable {
                        return $e->getPrevious();
                    }

                    echo gettype(getPrevious(new Exception("test")));',
            ],
            'exceptionGetTraceAsString' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     */
                    function getTraceAsString(Throwable $e): string {
                        return $e->getTraceAsString();
                    }

                    echo getTraceAsString(new Exception("test"));',
            ],
            'callingMethodInThrowStillPure' => [
                'code' => '<?php
                    final class MyException extends \Exception {
                        public static function hello(): self
                        {
                            return new self();
                        }
                    }

                    /**
                     * @psalm-pure
                     */
                    function sumExpectedToNotBlowPowerFuse(int $first, int $second): int {
                        $sum = $first + $second;
                        if ($sum > 9000) {
                            throw MyException::hello();
                        }
                        if ($sum > 900) {
                            throw new MyException();
                        }
                        return $sum;
                    }',
            ],
            'countMethodCanBePure' => [
                'code' => '<?php
                    class A implements Countable {
                        /** @psalm-mutation-free */
                        public function count(): int {
                            return 2;
                        }
                    }

                    /**
                     * @psalm-pure
                     */
                    function thePurest(A $countable): int {
                        return count($countable);
                    }',
            ],
            'mutationFreeAssertion' => [
                'code' => '<?php
                    class A {
                        private ?A $other = null;

                        public function setVar(A $other): void {
                            $this->other = $other;
                        }

                        /**
                         * @psalm-mutation-free
                         * @psalm-assert !null $this->other
                         */
                        public function checkNotNullNested(): bool {
                            if ($this->other === null) {
                                throw new RuntimeException("oops");
                            }

                            return !!$this->other->other;
                        }

                        public function foo() : void {}

                        public function doSomething(): void {
                            $this->checkNotNullNested();
                            $this->other->foo();
                        }
                    }',
            ],
            'allowPropertyAccessOnImmutableClass' => [
                'code' => '<?php
                    namespace Bar;

                    /** @psalm-immutable */
                    class A {
                        public int $a;

                        public function __construct(int $a) {
                            $this->a = $a;
                        }
                    }

                    /** @psalm-pure */
                    function filterOdd(A $a) : bool {
                        if ($a->a % 2 === 0) {
                            return true;
                        }

                        return false;
                    }',
            ],
            'allowPureInConstrucctorThis' => [
                'code' => '<?php
                    class Port {
                       private int $portNumber;

                       public function __construct(int $portNumber) {
                          if (!$this->isValidPort($portNumber)) {
                             throw new Exception();
                          }

                          $this->portNumber = $portNumber;
                       }

                       /**
                        * @psalm-pure
                        */
                       private function isValidPort(int $portNumber): bool {
                          return $portNumber >= 1 && $portNumber <= 1000;
                       }
                    }',
            ],
            'pureThroughCallStatic' => [
                'code' => '<?php

                    /**
                     * @method static self FOO()
                     * @method static static BAR()
                     * @method static static BAZ()
                     *
                     * @psalm-immutable
                     */
                    class MyEnum
                    {
                        const FOO = "foo";
                        const BAR = "bar";
                        const BAZ = "baz";

                        /** @psalm-pure */
                        public static function __callStatic(string $name, array $params): static
                        {
                            throw new BadMethodCallException("not implemented");
                        }
                    }

                    /** @psalm-pure */
                    function gimmeFoo(): MyEnum
                    {
                        return MyEnum::FOO();
                    }',
            ],
            'dontCrashWhileCheckingPurityOnCallStaticInATrait' => [
                'code' => '<?php
                    /**
                     * @method static static tt()
                     */
                    trait Date {
                        public static function __callStatic(string $_method, array $_parameters){
                        }
                    }

                    class Date2{
                        use Date;
                    }

                    Date2::tt();
                    ',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'impurePropertyAssignment' => [
                'code' => '<?php
                    namespace Bar;

                    class A {
                        public int $a = 5;
                    }

                    /** @psalm-pure */
                    function filterOdd(int $i, A $a) : ?int {
                        $a->a = $i;

                        if ($i % 2 === 0 || $a->a === 2) {
                            return $i;
                        }

                        return null;
                    }',
                'error_message' => 'ImpurePropertyAssignment',
            ],
            'impureMethodCall' => [
                'code' => '<?php
                    namespace Bar;

                    class A {
                        public int $a = 5;

                        public function foo() : void {
                            $this->a++;
                        }
                    }

                    /** @psalm-pure */
                    function filterOdd(int $i, A $a) : ?int {
                        $a->foo();

                        if ($i % 2 === 0 || $a->a === 2) {
                            return $i;
                        }

                        return null;
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'impureFunctionCall' => [
                'code' => '<?php
                    namespace Bar;

                    function impure() : ?string {
                        /** @var int */
                        static $i = 0;

                        ++$i;

                        return $i % 2 ? "hello" : null;
                    }

                    /** @psalm-pure */
                    function filterOdd(array $a) : void {
                        impure();
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'impureConstructorCall' => [
                'code' => '<?php
                    namespace Bar;

                    class A {
                        public int $a = 5;
                    }

                    class B {
                        public function __construct(A $a) {
                            $a->a++;
                        }
                    }

                    /** @psalm-pure */
                    function filterOdd(int $i, A $a) : ?int {
                        $b = new B($a);

                        if ($i % 2 === 0 || $a->a === 2) {
                            return $i;
                        }

                        return null;
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'canCreateObjectWithNoExternalMutations' => [
                'code' => '<?php
                    class Counter {
                        private int $count = 0;

                        public function __construct(int $count) {
                            $this->count = $count;
                        }

                        public function increment() : void {
                            $this->count += rand(0, 5);
                        }
                    }

                    /** @psalm-pure */
                    function makesACounter(int $i) : Counter {
                        $c = new Counter($i);
                        $c->increment();
                        return $c;
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'useOfStaticMakesFunctionImpure' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function addCumulative(int $left) : int {
                        /** @var int */
                        static $i = 0;
                        $i += $left;
                        return $left;
                    }',
                'error_message' => 'ImpureStaticVariable',
            ],
            'preventImpureArrayMapClosure' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @param string[] $arr
                     */
                    function foo(array $arr) : array {
                        return \array_map(function(string $s) { return $s . rand(0, 1);}, $arr);
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'sortFunctionImpure' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     *
                     * @param int[] $ar
                     */
                    function foo(array $ar): int
                    {
                        usort($ar, static function (int $a, int $b): int {
                            session_start();
                            return $a <=> $b;
                        });

                        return $ar[0] ?? 0;
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'printFunctionIsImpure' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function foo(): void {
                        print("x");
                    }
                ',
                'error_message' => 'ImpureFunctionCall',
            ],
            'exitFunctionWithNonIntegerArgumentIsImpure' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function foo(): void {
                        exit("x");
                    }
                ',
                'error_message' => 'ImpureFunctionCall',
            ],
            'dieFunctionWithNonIntegerArgumentIsImpure' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function foo(): void {
                        die("x");
                    }
                ',
                'error_message' => 'ImpureFunctionCall',
            ],
            'impureByRef' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     */
                    function foo(string &$a): string {
                        $a = "B";
                        return $a;
                    }',
                'error_message' => 'ImpureByReferenceAssignment',
            ],
            'staticPropertyFetch' => [
                'code' => '<?php
                    final class Number1 {
                        public static ?string $zero = null;

                        /**
                         * @psalm-pure
                         */
                        public static function zero(): ?string {
                            return self::$zero;
                        }
                    }',
                'error_message' => 'ImpureStaticProperty',
            ],
            'staticPropertyAssignment' => [
                'code' => '<?php
                    final class Number1 {
                        /** @var string|null */
                        private static $zero;

                        /**
                         * @psalm-pure
                         */
                        public static function zero(): string {
                            self::$zero = "Zero";
                            return "hello";
                        }
                    }',
                'error_message' => 'ImpureStaticProperty',
            ],
            'preventImpureToStringViaComparison' => [
                'code' => '<?php
                    class A {
                        public function __toString() {
                            echo "hi";
                            return "bar";
                        }
                    }

                    /**
                     * @psalm-pure
                     */
                    function foo(string $s, A $a) : string {
                        if ($a == $s) {}
                        return $s;
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'preventImpureToStringViaConcatenation' => [
                'code' => '<?php
                    class A {
                        public function __toString() {
                            echo "hi";
                            return "bar";
                        }
                    }

                    /**
                     * @psalm-pure
                     */
                    function foo(string $s, A $a) : string {
                        return $a . $s;
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'countCanBeImpure' => [
                'code' => '<?php
                    class A implements Countable {
                        public function count(): int {
                            echo "oops";
                            return 2;
                        }
                    }

                    /**
                     * @psalm-pure
                     */
                    function thePurest(A $countable): int {
                        return count($countable);
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'propertyFetchIsNotPure' => [
                'code' => '<?php
                    class A {
                        public string $foo = "hello";

                        /** @psalm-pure */
                        public static function getFoo(A $a) : string {
                            return $a->foo;
                        }
                    }',
                'error_message' => 'ImpurePropertyFetch',
            ],
            'preventPropertyAccessOnMutableClass' => [
                'code' => '<?php
                    namespace Bar;

                    class A {
                        public int $a;

                        public function __construct(int $a) {
                            $this->a = $a;
                        }
                    }

                    /** @psalm-pure */
                    function filterOdd(A $a) : bool {
                        if ($a->a % 2 === 0) {
                            return true;
                        }

                        return false;
                    }',
                'error_message' => 'ImpurePropertyFetch',
            ],
            'preventIssetOnMutableClassKnownProperty' => [
                'code' => '<?php
                    namespace Bar;

                    class A {
                        public ?int $a;

                        public function __construct(?int $a) {
                            $this->a = $a;
                        }
                    }

                    /** @psalm-pure */
                    function filterOdd(A $a) : bool {
                        if (isset($a->a)) {
                            return true;
                        }

                        return false;
                    }',
                'error_message' => 'ImpurePropertyFetch',
            ],
            'preventIssetOnMutableClassUnknownProperty' => [
                'code' => '<?php
                    namespace Bar;

                    class A {
                        public ?int $a;

                        public function __construct(?int $a) {
                            $this->a = $a;
                        }
                    }

                    /** @psalm-pure */
                    function filterOdd(A $a) : bool {
                        if (isset($a->b)) {
                            return true;
                        }

                        return false;
                    }',
                'error_message' => 'ImpurePropertyFetch',
            ],
            'impureThis' => [
                'code' => '<?php
                    class A {
                        public int $a = 5;

                        /**
                         * @psalm-pure
                         */
                        public function foo() : self {
                            return $this;
                        }
                    }',
                'error_message' => 'ImpureVariable',
            ],
            'iterableIsNotPure' => [
                'code' => '<?php
                    namespace Test;

                    /**
                     * @param iterable<string> $pieces
                     *
                     * @psalm-pure
                     */
                    function foo(iterable $pieces): string
                    {
                        foreach ($pieces as $piece) {
                            return $piece;
                        }

                        return "jello";
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'impureThroughCallStatic' => [
                'code' => '<?php
                    /**
                     * @method static void test()
                     */
                    final class Impure
                    {
                        public static function __callStatic(string $name, array $arguments)
                        {
                        }
                    }

                    /**
                     * @psalm-pure
                     */
                    function testImpure(): void
                    {
                        Impure::test();
                    }
                    ',
                'error_message' => 'ImpureMethodCall',
            ],
            'impureCallableInImmutableContext' => [
                'code' => '<?php

                    /**
                     * @psalm-immutable
                     */
                    class Either
                    {
                        /**
                         * @psalm-param callable $_
                         */
                        public function fold($_): void
                        {
                            $_();
                        }
                    }

                    class Whatever
                    {
                        public function __construct()
                        {
                            $either = new Either();
                            $either->fold(
                                function (): void {}
                            );
                        }
                    }

                    new Whatever();
                    ',
                'error_message' => 'ImpureFunctionCall',
            ],
        ];
    }
}
