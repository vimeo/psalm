<?php
namespace Psalm\Tests;

use function class_exists;
use const DIRECTORY_SEPARATOR;

class MethodCallTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return void
     */
    public function testExtendDocblockParamType()
    {
        if (class_exists('SoapClient') === false) {
            $this->markTestSkipped('Cannot run test, base class "SoapClient" does not exist!');

            return;
        }

        $this->addFile(
            'somefile.php',
            '<?php
                new SoapFault("1", "faultstring", "faultactor");'
        );

        $this->analyzeFile('somefile.php', new \Psalm\Context());
    }

    public function testMethodCallMemoize(): void
    {
        $this->project_analyzer->getConfig()->memoize_method_calls = true;

        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    function getFoo() : ?Foo {
                        return rand(0, 1) ? new Foo : null;
                    }
                }
                class Foo {
                    function getBar() : ?Bar {
                        return rand(0, 1) ? new Bar : null;
                    }
                }
                class Bar {
                    public function bat() : void {}
                };

                $a = new A();

                if ($a->getFoo()) {
                    if ($a->getFoo()->getBar()) {
                        $a->getFoo()->getBar()->bat();
                    }
                }'
        );

        $this->analyzeFile('somefile.php', new \Psalm\Context());
    }

    public function testPropertyMethodCallMemoize(): void
    {
        $this->project_analyzer->getConfig()->memoize_method_calls = true;

        $this->addFile(
            'somefile.php',
            '<?php
                class Foo
                {
                    private ?string $bar;

                    public function __construct(?string $bar) {
                        $this->bar = $bar;
                    }

                    public function getBar(): ?string {
                        return $this->bar;
                    }
                }

                function doSomething(Foo $foo): string {
                    if ($foo->getBar() !== null){
                        return $foo->getBar();
                    }

                    return "hello";
                }'
        );

        $this->analyzeFile('somefile.php', new \Psalm\Context());
    }

    public function testPropertyMethodCallMutationFreeMemoize(): void
    {
        $this->project_analyzer->getConfig()->memoize_method_calls = true;

        $this->addFile(
            'somefile.php',
            '<?php
                class Foo
                {
                    private ?string $bar;

                    public function __construct(?string $bar) {
                        $this->bar = $bar;
                    }

                    /**
                     * @psalm-mutation-free
                     */
                    public function getBar(): ?string {
                        return $this->bar;
                    }
                }

                function doSomething(Foo $foo): string {
                    if ($foo->getBar() !== null){
                        return $foo->getBar();
                    }

                    return "hello";
                }'
        );

        $this->analyzeFile('somefile.php', new \Psalm\Context());
    }

    public function testUnchainedMethodCallMemoize(): void
    {
        $this->project_analyzer->getConfig()->memoize_method_calls = true;

        $this->addFile(
            'somefile.php',
            '<?php
                class SomeClass {
                    private ?int $int;

                    public function __construct() {
                        $this->int = 1;
                    }

                    final public function getInt(): ?int {
                        return $this->int;
                    }
                }

                function printInt(int $int): void {
                    echo $int;
                }

                $obj = new SomeClass();

                if ($obj->getInt()) {
                    printInt($obj->getInt());
                }'
        );

        $this->analyzeFile('somefile.php', new \Psalm\Context());
    }

    public function testUnchainedMutationFreeMethodCallMemoize(): void
    {
        $this->project_analyzer->getConfig()->memoize_method_calls = true;

        $this->addFile(
            'somefile.php',
            '<?php
                class SomeClass {
                    private ?int $int;

                    public function __construct() {
                        $this->int = 1;
                    }

                    /**
                     * @psalm-mutation-free
                     */
                    public function getInt(): ?int {
                        return $this->int;
                    }
                }

                function printInt(int $int): void {
                    echo $int;
                }

                $obj = new SomeClass();

                if ($obj->getInt()) {
                    printInt($obj->getInt());
                }'
        );

        $this->analyzeFile('somefile.php', new \Psalm\Context());
    }

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'notInCallMapTest' => [
                '<?php
                    new DOMImplementation();',
            ],
            'parentStaticCall' => [
                '<?php
                    class A {
                        /** @return void */
                        public static function foo(){}
                    }

                    class B extends A {
                        /** @return void */
                        public static function bar(){
                            parent::foo();
                        }
                    }',
            ],
            'nonStaticInvocation' => [
                '<?php
                    class Foo {
                        public static function barBar(): void {}
                    }

                    (new Foo())->barBar();',
            ],
            'staticInvocation' => [
                '<?php
                    class A {
                        public static function fooFoo(): void {}
                    }

                    class B extends A {

                    }

                    B::fooFoo();',
            ],
            'staticCallOnVar' => [
                '<?php
                    class A {
                        public static function bar(): int {
                            return 5;
                        }
                    }
                    $foo = new A;
                    $b = $foo::bar();',
            ],
            'uppercasedSelf' => [
                '<?php
                    class X33{
                        public static function main(): void {
                            echo SELF::class . "\n";  // Class or interface SELF does not exist
                        }
                    }
                    X33::main();',
            ],
            'dateTimeImmutableStatic' => [
                '<?php
                    /** @psalm-immutable */
                    final class MyDate extends DateTimeImmutable {}

                    $today = new MyDate();
                    $yesterday = $today->sub(new DateInterval("P1D"));

                    $b = (new DateTimeImmutable())->modify("+3 hours");',
                'assertions' => [
                    '$yesterday' => 'MyDate|false',
                    '$b' => 'DateTimeImmutable',
                ],
            ],
            'magicCall' => [
                '<?php
                    class A {
                        public function __call(string $method_name, array $args) : string {
                            return "hello";
                        }
                    }

                    $a = new A;
                    $s = $a->bar();',
                [
                    '$s' => 'string',
                ]
            ],
            'canBeCalledOnMagic' => [
                '<?php
                    class A {
                      public function __call(string $method, array $args) {}
                    }

                    class B {}

                    $a = rand(0, 1) ? new A : new B;

                    $a->maybeUndefinedMethod();',
                'assertions' => [],
                'error_levels' => ['PossiblyUndefinedMethod'],
            ],
            'canBeCalledOnMagicWithMethod' => [
                '<?php
                    class A {
                      public function __call(string $method, array $args) {}
                    }

                    class B {
                        public function bar() : void {}
                    }

                    $a = rand(0, 1) ? new A : new B;

                    $a->bar();',
                'assertions' => [],
            ],
            'invokeCorrectType' => [
                '<?php
                    class A {
                        public function __invoke(string $p): void {}
                    }

                    $q = new A;
                    $q("asda");',
            ],
            'domDocumentAppendChild' => [
                '<?php
                    $doc = new DOMDocument("1.0");
                    $node = $doc->createElement("foo");
                    if ($node instanceof DOMElement) {
                        $newnode = $doc->appendChild($node);
                        $newnode->setAttribute("bar", "baz");
                    }',
            ],
            'nonStaticSelfCall' => [
                '<?php
                    class A11 {
                        public function call() : self {
                            $result = self::method();
                            return $result;
                        }

                        public function method() : self {
                            return $this;
                        }
                    }
                    $x = new A11();
                    var_export($x->call());',
            ],
            'simpleXml' => [
                '<?php
                    $xml = new SimpleXMLElement("<a><b></b></a>");
                    $a = $xml->asXML();
                    $b = $xml->asXML("foo.xml");',
                'assertions' => [
                    '$a' => 'false|string',
                    '$b' => 'bool',
                ],
            ],
            'datetimeformatNotFalse' => [
                '<?php
                    $format = random_bytes(10);
                    $dt = new DateTime;
                    $formatted = $dt->format($format);
                    if (false !== $formatted) {}
                    function takesString(string $s) : void {}
                    takesString($formatted);',
            ],
            'domElement' => [
                '<?php
                    function foo(DOMElement $e) : ?string {
                        $a = $e->getElementsByTagName("bar");
                        $b = $a->item(0);
                        if (!$b) {
                            return null;
                        }
                        return $b->getAttribute("bat");
                    }',
            ],
            'domElementIteratorOrEmptyArray' => [
                '<?php
                    function foo(string $XML) : void {
                        $dom = new DOMDocument();
                        $dom->loadXML($XML);

                        $elements = rand(0, 1) ? $dom->getElementsByTagName("bar") : [];
                        foreach ($elements as $element) {
                            $element->getElementsByTagName("bat");
                        }
                    }',
            ],
            'reflectionParameter' => [
                '<?php
                    function getTypeName(ReflectionParameter $parameter): string {
                        $type = $parameter->getType();

                        if ($type === null) {
                            return "mixed";
                        }

                        return $type->getName();
                    }',
            ],
            'PDOMethod' => [
                '<?php
                    function md5_and_reverse(string $string) : string {
                        return strrev(md5($string));
                    }

                    $db = new PDO("sqlite:sqlitedb");
                    $db->sqliteCreateFunction("md5rev", "md5_and_reverse", 1);',
            ],
            'dontConvertedMaybeMixedAfterCall' => [
                '<?php
                    class B {
                        public function foo() : void {}
                    }
                    /**
                     * @param array<B> $b
                     */
                    function foo(array $a, array $b) : void {
                        $c = array_merge($b, $a);

                        foreach ($c as $d) {
                            $d->foo();
                            if ($d instanceof B) {}
                        }
                    }',
                [],
                'error_levels' => ['MixedAssignment', 'MixedMethodCall'],
            ],
            'methodResolution' => [
                '<?php
                    interface ReturnsString {
                        public function getId(): string;
                    }

                    /**
                     * @param mixed $a
                     */
                    function foo(ReturnsString $user, $a): string {
                        strlen($user->getId());

                        (is_object($a) && method_exists($a, "getS")) ? (string)$a->getS() : "";

                        return $user->getId();
                    }',
            ],
            'defineVariableCreatedInArgToMixed' => [
                '<?php
                    function bar($a) : void {
                        if ($a->foo($b = (int) "5")) {
                            echo $b;
                        }
                    }',
                [],
                'error_levels' => ['MixedMethodCall', 'MissingParamType'],
            ],
            'staticCallAfterMethodExists' => [
                '<?php
                    class A
                    {
                        protected static function existing() : string
                        {
                            return "hello";
                        }

                        protected static function foo() : string
                        {
                            if (!method_exists(static::class, "maybeExists")) {
                                return "hello";
                            }

                            self::maybeExists();

                            return static::existing();
                        }
                    }',
            ],
            'varSelfCall' => [
                '<?php
                    class Foo {
                        /** @var self */
                        public static $current;
                        public function bar() : void {}
                    }

                    Foo::$current->bar();',
            ],
            'pdoStatementSetFetchMode' => [
                '<?php
                    class A {
                        /** @var ?string */
                        public $a;
                    }

                    $db = new PDO("sqlite::memory:");
                    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $stmt = $db->prepare("select \"a\" as a");
                    $stmt->setFetchMode(PDO::FETCH_CLASS, A::class);
                    $stmt->execute();
                    /** @psalm-suppress MixedAssignment */
                    $a = $stmt->fetch();',
            ],
            'datePeriodConstructor' => [
                '<?php
                    function foo(DateTime $d1, DateTime $d2) : void {
                        new DatePeriod(
                            $d1,
                            DateInterval::createFromDateString("1 month"),
                            $d2
                        );
                    }',
            ],
            'callMethodAfterCheckingExistence' => [
                '<?php
                    class A {}

                    function foo(A $a) : void {
                        if (method_exists($a, "bar")) {
                            /** @psalm-suppress MixedArgument */
                            echo $a->bar();
                        }
                    }'
            ],
            'callMethodAfterCheckingExistenceInClosure' => [
                '<?php
                    class A {}

                    function foo(A $a) : void {
                        if (method_exists($a, "bar")) {
                            (function() use ($a) : void {
                                /** @psalm-suppress MixedArgument */
                                echo $a->bar();
                            })();

                        }
                    }'
            ],
            'callManyMethodsAfterCheckingExistence' => [
                '<?php
                    function foo(object $object) : void {
                        if (!method_exists($object, "foo")) {
                            return;
                        }
                        if (!method_exists($object, "bar")) {
                            return;
                        }
                        $object->foo();
                        $object->bar();
                    }'
            ],
            'callManyMethodsAfterCheckingExistenceChained' => [
                '<?php
                    function foo(object $object) : void {
                        if (method_exists($object, "foo") && method_exists($object, "bar")) {
                            $object->foo();
                            $object->bar();
                        }
                    }'
            ],
            'callManyMethodsOnKnownObjectAfterCheckingExistenceChained' => [
                '<?php
                    class A {}
                    function foo(A $object) : void {
                        if (method_exists($object, "foo") && method_exists($object, "bar")) {
                            $object->foo();
                            $object->bar();
                        }
                    }'
            ],
            'preserveMethodExistsType' => [
                '<?php
                    /**
                     * @param class-string $foo
                     */
                    function foo(string $foo): string {
                        if (!method_exists($foo, "something")) {
                            return "";
                        }

                        return $foo;
                    }'
            ],
            'methodDoesNotExistOnClass' => [
                '<?php
                    class A {}

                    /**
                     * @param class-string<A> $foo
                     */
                    function foo(string $foo): string {
                        if (!method_exists($foo, "something")) {
                            return "";
                        }

                        return $foo;
                    }'
            ],
            'pdoStatementFetchAssoc' => [
                '<?php
                    /** @return array<string,null|scalar>|false */
                    function fetch_assoc() {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetch(PDO::FETCH_ASSOC);
                    }'
            ],
            'pdoStatementFetchBoth' => [
                '<?php
                    /** @return array<null|scalar>|false */
                    function fetch_both() {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetch(PDO::FETCH_BOTH);
                    }'
            ],
            'pdoStatementFetchBound' => [
                '<?php
                    /** @return bool */
                    function fetch_both() : bool {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetch(PDO::FETCH_BOUND);
                    }'
            ],
            'pdoStatementFetchClass' => [
                '<?php
                    /** @return object|false */
                    function fetch_class() {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetch(PDO::FETCH_CLASS);
                    }'
            ],
            'pdoStatementFetchLazy' => [
                '<?php
                    /** @return object|false */
                    function fetch_lazy() {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetch(PDO::FETCH_LAZY);
                    }'
            ],
            'pdoStatementFetchNamed' => [
                '<?php
                    /** @return array<string,scalar|list<scalar>>|false */
                    function fetch_named() {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetch(PDO::FETCH_NAMED);
                    }'
            ],
            'pdoStatementFetchNum' => [
                '<?php
                    /** @return list<null|scalar>|false */
                    function fetch_named() {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetch(PDO::FETCH_NUM);
                    }'
            ],
            'pdoStatementFetchObj' => [
                '<?php
                    /** @return stdClass|false */
                    function fetch_named() {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetch(PDO::FETCH_OBJ);
                    }'
            ],
            'dateTimeSecondArg' => [
                '<?php
                    $date = new DateTime(null, new DateTimeZone("Pacific/Nauru"));
                    echo $date->format("Y-m-d H:i:sP") . "\n";'
            ],
            'noCrashOnGetClassMethodCallWithNull' => [
                '<?php
                    class User {
                        /**
                         * @psalm-suppress NullArgument
                         */
                        public function give(): void{
                            $model = null;
                            $class = \get_class($model);
                            $class::foo();
                        }
                    }',
            ],
            'unknownMethodCallWithProperty' => [
                '<?php
                    class A {
                        private string $b = "c";

                        public function passesByRef(object $a): void {
                            /** @psalm-suppress MixedMethodCall */
                            $a->passedByRef($this->b);
                        }
                    }',
            ],
            'maybeNotTooManyArgumentsToInstance' => [
                '<?php
                    class A {
                        public function fooFoo(int $a): void {}
                    }

                    class B {
                        public function fooFoo(int $a, string $s): void {}
                    }

                    (rand(0, 1) ? new A : new B)->fooFoo(5, "dfd");',
            ],
            'interfaceMethodCallCheck' => [
                '<?php
                    interface A {
                        function foo() : void;
                    }

                    interface B extends A {
                        function foo(string $a = "") : void;
                    }

                    class C implements B {
                        public function foo(string $a = "") : void {}
                    }

                    function takesWithoutArguments(A $a) : void {
                        if ($a instanceof B) {
                           $a->foo("");
                        }
                    }

                    takesWithoutArguments(new C);'
            ],
            'getterAutomagicAssertion' => [
                '<?php
                    class A {
                        /** @var string|null */
                        public $a;

                        /** @return string|null */
                        final public function getA() {
                            return $this->a;
                        }
                    }

                    $a = new A();

                    if ($a->getA()) {
                        echo strlen($a->getA());
                    }'
            ],
            'ignorePossiblyNull' => [
                '<?php
                    class Foo {
                        protected ?string $type = null;

                        public function prepend(array $arr) : string {
                            return $this->getType();
                        }

                        /**
                         * @psalm-ignore-nullable-return
                         */
                        public function getType() : ?string
                        {
                            return $this->type;
                        }
                    }'
            ],
            'abstractMethodExistsOnChild' => [
                '<?php
                    abstract class Foo {}

                    abstract class FooChild extends Foo {}

                    abstract class AbstractTestCase {
                        abstract public function createFoo(): Foo;
                    }

                    abstract class AbstractChildTestCase extends AbstractTestCase {
                        abstract public function createFoo(): FooChild;

                        public function testFoo(): FooChild {
                            return $this->createFoo();
                        }
                    }',
                [],
                [],
                '7.4'
            ],
            'pdoQueryTwoArgs' => [
                '<?php
                    $pdo = new PDO("test");
                    $pdo->query("SELECT * FROM projects", PDO::FETCH_NAMED);'
            ],
            'unchainedInferredMutationFreeMethodCallMemoize' => [
                '<?php
                    class SomeClass {
                        private ?int $int;

                        public function __construct() {
                            $this->int = 1;
                        }

                        /**
                         * @psalm-mutation-free
                         */
                        public function getInt(): ?int {
                            return $this->int;
                        }
                    }

                    function printInt(int $int): void {
                        echo $int;
                    }

                    $obj = new SomeClass();

                    if ($obj->getInt()) {
                        printInt($obj->getInt());
                    }',
            ],
            'unchainedInferredInferredFinalMutationFreeMethodCallMemoize' => [
                '<?php
                    class SomeClass {
                        private ?int $int;

                        public function __construct() {
                            $this->int = 1;
                        }

                        final public function getInt(): ?int {
                            return $this->int;
                        }
                    }

                    function printInt(int $int): void {
                        echo $int;
                    }

                    $obj = new SomeClass();

                    if ($obj->getInt()) {
                        printInt($obj->getInt());
                    }',
            ],
            'privateInferredMutationFreeMethodCallMemoize' => [
                '<?php
                    class PropertyClass {
                        public function test() : void {
                            echo "test";
                        }
                    }
                    class SomeClass {
                        private ?PropertyClass $property = null;

                        private function getProperty(): ?PropertyClass {
                            return $this->property;
                        }

                        public function test(int $int): void {
                            if ($this->getProperty() !== null) {
                                $this->getProperty()->test();
                            }
                        }
                    }',
            ],
            'inferredFinalMethod' => [
                '<?php
                    class PropertyClass {
                        public function test() : bool {
                            return true;
                        }
                    }

                    class MainClass {
                        private ?PropertyClass $property = null;

                        final public function getProperty(): ?PropertyClass {
                            return $this->property;
                        }
                    }

                    $main = new MainClass();

                    if ($main->getProperty() !== null && $main->getProperty()->test()) {}'
            ],
            'getterTypeInferring' => [
                '<?php
                    class A {
                        /** @var int|string|null */
                        public $a;

                        /** @return int|string|null */
                        final public function getValue() {
                            return $this->a;
                        }
                    }

                    $a = new A();

                    if (is_string($a->getValue())) {
                        echo strlen($a->getValue());
                    }',
            ],
            'newSplObjectStorageDefaultEmpty' => [
                '<?php
                    $a = new SplObjectStorage();',
                [
                    '$a' => 'SplObjectStorage<empty, empty>',
                ]
            ],
            'allowIteratorToBeNull' => [
                '<?php
                    /**
                     * @return Iterator<string>
                     */
                    function buildIterator(int $size): Iterator {

                        $values = [];
                        for ($i = 0;  $i < $size; $i++) {
                           $values[] = "Item $i\n";
                        }

                        return new ArrayIterator($values);
                    }

                    $it = buildIterator(2);

                    if ($it->current() === null) {}'
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'staticInvocation' => [
                '<?php
                    class Foo {
                        public function barBar(): void {}
                    }

                    Foo::barBar();',
                'error_message' => 'InvalidStaticInvocation',
            ],
            'parentStaticCall' => [
                '<?php
                    class A {
                        /** @return void */
                        public function foo(){}
                    }

                    class B extends A {
                        /** @return void */
                        public static function bar(){
                            parent::foo();
                        }
                    }',
                'error_message' => 'InvalidStaticInvocation',
            ],
            'mixedMethodCall' => [
                '<?php
                    class Foo {
                        public static function barBar(): void {}
                    }

                    /** @var mixed */
                    $a = (new Foo());

                    $a->barBar();',
                'error_message' => 'MixedMethodCall',
                'error_levels' => [
                    'MissingPropertyType',
                    'MixedAssignment',
                ],
            ],
            'invalidMethodCall' => [
                '<?php
                    ("hello")->someMethod();',
                'error_message' => 'InvalidMethodCall',
            ],
            'possiblyInvalidMethodCall' => [
                '<?php
                    class A1 {
                        public function methodOfA(): void {
                        }
                    }

                    /** @param A1|string $x */
                    function example($x, bool $isObject) : void {
                        if ($isObject) {
                            $x->methodOfA();
                        }
                    }',
                'error_message' => 'PossiblyInvalidMethodCall',
            ],
            'selfNonStaticInvocation' => [
                '<?php
                    class A {
                        public function fooFoo(): void {}

                        public static function barBar(): void {
                            self::fooFoo();
                        }
                    }',
                'error_message' => 'NonStaticSelfCall',
            ],
            'noParent' => [
                '<?php
                    class Foo {
                        public function barBar(): void {
                            parent::barBar();
                        }
                    }',
                'error_message' => 'ParentNotFound',
            ],
            'coercedClass' => [
                '<?php
                    class NullableClass {
                    }

                    class NullableBug {
                        /**
                         * @param class-string|null $className
                         * @return object|null
                         */
                        public static function mock($className) {
                            if (!$className) { return null; }
                            return new $className();
                        }

                        /**
                         * @return ?NullableClass
                         */
                        public function returns_nullable_class() {
                            /** @psalm-suppress ArgumentTypeCoercion */
                            return self::mock("NullableClass");
                        }
                    }',
                'error_message' => 'LessSpecificReturnStatement',
                'error_levels' => ['MixedInferredReturnType', 'MixedReturnStatement', 'MixedMethodCall'],
            ],
            'undefinedVariableStaticCall' => [
                '<?php
                    $foo::bar();',
                'error_message' => 'UndefinedGlobalVariable',
            ],
            'staticCallOnString' => [
                '<?php
                    class A {
                        public static function bar(): int {
                            return 5;
                        }
                    }
                    $foo = "A";
                    /** @psalm-suppress InvalidStringClass */
                    $b = $foo::bar();',
                'error_message' => 'MixedAssignment',
            ],
            'possiblyNullFunctionCall' => [
                '<?php
                    $this->foo();',
                'error_message' => 'InvalidScope',
            ],
            'possiblyFalseReference' => [
                '<?php
                    class A {
                        public function bar(): void {}
                    }

                    $a = rand(0, 1) ? new A : false;
                    $a->bar();',
                'error_message' => 'PossiblyFalseReference',
            ],
            'undefinedParentClass' => [
                '<?php
                    /**
                     * @psalm-suppress UndefinedClass
                     */
                    class B extends A {}

                    $b = new B();',
                'error_message' => 'MissingDependency - src' . DIRECTORY_SEPARATOR . 'somefile.php:7',
            ],
            'variableMethodCallOnArray' => [
                '<?php
                    $arr = [];
                    $b = "foo";
                    $arr->$b();',
                'error_message' => 'InvalidMethodCall',
            ],
            'intVarStaticCall' => [
                '<?php
                    $a = 5;
                    $a::bar();',
                'error_message' => 'UndefinedClass',
            ],
            'intVarNewCall' => [
                '<?php
                    $a = 5;
                    new $a();',
                'error_message' => 'UndefinedClass',
            ],
            'invokeTypeMismatch' => [
                '<?php
                    class A {
                        public function __invoke(string $p): void {}
                    }

                    $q = new A;
                    $q(1);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'explicitInvokeTypeMismatch' => [
                '<?php
                    class A {
                        public function __invoke(string $p): void {}
                    }
                    (new A)->__invoke(1);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'undefinedMethodPassedAsArg' => [
                '<?php
                    class A {
                        public function __call(string $method, array $args) {}
                    }

                    $q = new A;
                    $q->foo(bar());',
                'error_message' => 'UndefinedFunction',
            ],
            'noIntersectionMethod' => [
                '<?php
                    interface A {}
                    interface B {}

                    /** @param B&A $p */
                    function f($p): void {
                        $p->zugzug();
                    }',
                'error_message' => 'UndefinedInterfaceMethod - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:29 - Method (B&A)::zugzug does not exist',
            ],
            'noInstanceCallAsStatic' => [
                '<?php
                    class C {
                        public function foo() : void {}
                    }

                    (new C)::foo();',
                'error_message' => 'InvalidStaticInvocation',
            ],
            'noExceptionOnMissingClass' => [
                '<?php
                    /** @psalm-suppress UndefinedClass */
                    class A
                    {
                        /** @var class-string<Foo> */
                        protected $bar;

                        public function foo(string $s): void
                        {
                            $bar = $this->bar;
                            $bar::baz();
                        }
                    }',
                'error_message' => 'UndefinedClass',
            ],
            'checkMixedMethodCallStaticMethodCallArg' => [
                '<?php
                    class B {}
                    /** @param mixed $a */
                    function foo($a) : void {
                        /** @psalm-suppress MixedMethodCall */
                        $a->bar(B::bat());
                    }',
                'error_message' => 'UndefinedMethod',
            ],
            'complainAboutUndefinedPropertyOnMixedCall' => [
                '<?php
                    class C {
                        /** @param mixed $a */
                        public function foo($a) : void {
                            /** @psalm-suppress MixedMethodCall */
                            $a->bar($this->d);
                        }
                    }',
                'error_message' => 'UndefinedThisPropertyFetch',
            ],
            'complainAboutUndefinedPropertyOnMixedCallConcatOp' => [
                '<?php
                    class A {
                        /**
                         * @psalm-suppress MixedMethodCall
                         */
                        public function foo(object $a) : void {
                            $a->bar("bat" . $this->baz);
                        }
                    }',
                'error_message' => 'UndefinedThisPropertyFetch',
            ],
            'alreadyHasmethod' => [
                '<?php
                    class A {
                        public function foo() : void {}
                    }

                    function foo(A $a) : void {
                        if (method_exists($a, "foo")) {
                            $object->foo();
                        }
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'possiblyNullOrMixedArg' => [
                '<?php
                    class A {
                        /**
                         * @var mixed
                         */
                        public $foo;
                    }

                    function takesString(string $s) : void {}

                    function takesA(?A $a) : void {
                        /**
                         * @psalm-suppress PossiblyNullPropertyFetch
                         * @psalm-suppress MixedArgument
                         */
                        takesString($a->foo);
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'callOnVoid' => [
                '<?php
                    class A {
                        public function foo(): void {}
                    }

                    $p = new A();
                    $p->foo()->bar();',
                'error_message' => 'NullReference'
            ],
            'dateTimeNullFirstArg' => [
                '<?php
                    $date = new DateTime(null);',
                'error_message' => 'NullArgument'
            ],
            'noCrashOnGetClassMethodCall' => [
                '<?php
                    class User {
                        /**
                         * @psalm-suppress MixedArgument
                         */
                        public function give(): void{
                            /** @var mixed */
                            $model = null;
                            $class = \get_class($model);
                            $class::foo();
                        }
                    }',
                'error_message' => 'InvalidStringClass',
            ],
            'preventAbstractMethodCall' => [
                '<?php
                    abstract class Base {
                        public static function callAbstract() : void {
                            static::bar();
                        }

                        abstract static function bar() : void;
                    }

                    Base::bar();',
                'error_message' => 'AbstractMethodCall',
            ],
            'tooManyArgumentsToStatic' => [
                '<?php
                    class A {
                        public static function fooFoo(int $a): void {}
                    }

                    A::fooFoo(5, "dfd");',
                'error_message' => 'TooManyArguments',
            ],
            'tooFewArgumentsToStatic' => [
                '<?php
                    class A {
                        public static function fooFoo(int $a): void {}
                    }

                    A::fooFoo();',
                'error_message' => 'TooFewArguments',
            ],
            'tooManyArgumentsToInstance' => [
                '<?php
                    class A {
                        public function fooFoo(int $a): void {}
                    }

                    (new A)->fooFoo(5, "dfd");',
                'error_message' => 'TooManyArguments',
            ],
            'tooFewArgumentsToInstance' => [
                '<?php
                    class A {
                        public function fooFoo(int $a): void {}
                    }

                    (new A)->fooFoo();',
                'error_message' => 'TooFewArguments',
            ],
            'getterAutomagicOverridden' => [
                '<?php
                    class A {
                        /** @var string|null */
                        public $a;

                        /** @return string|null */
                        function getA() {
                            return $this->a;
                        }
                    }

                    class AChild extends A {
                        function getA() {
                            return rand(0, 1) ? $this->a : null;
                        }
                    }

                    function foo(A $a) : void {
                        if ($a->getA()) {
                            echo strlen($a->getA());
                        }
                    }

                    foo(new AChild());',
                'error_message' => 'PossiblyNullArgument'
            ],
            'getterAutomagicOverriddenWithAssertion' => [
                '<?php
                    class A {
                        /** @var string|null */
                        public $a;

                        /** @psalm-assert-if-true string $this->a */
                        function hasA() {
                            return is_string($this->a);
                        }

                        /** @return string|null */
                        function getA() {
                            return $this->a;
                        }
                    }

                    class AChild extends A {
                        function getA() {
                            return rand(0, 1) ? $this->a : null;
                        }
                    }

                    function foo(A $a) : void {
                        if ($a->hasA()) {
                            echo strlen($a->getA());
                        }
                    }

                    foo(new AChild());',
                'error_message' => 'PossiblyNullArgument'
            ],
            'checkVariableInUnknownClassConstructor' => [
                '<?php
                    /** @psalm-suppress UndefinedClass */
                    new Missing($class_arg);',
                'error_message' => 'PossiblyUndefinedVariable',
            ],
            'unchainedInferredInferredMutationFreeMethodCallDontMemoize' => [
                '<?php
                    class SomeClass {
                        private ?int $int;

                        public function __construct() {
                            $this->int = 1;
                        }

                        public function getInt(): ?int {
                            return $this->int;
                        }
                    }

                    function printInt(int $int): void {
                        echo $int;
                    }

                    $obj = new SomeClass();

                    if ($obj->getInt()) {
                        printInt($obj->getInt());
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'getterTypeInferringWithChange' => [
                '<?php
                    class A {
                        /** @var int|string|null */
                        public $val;

                        /** @return int|string|null */
                        final public function getValue() {
                            return $this->val;
                        }
                    }

                    $a = new A();

                    if (is_string($a->getValue())) {
                        $a->val = 5;
                        echo strlen($a->getValue());
                    }',
                'error_message' => 'InvalidScalarArgument',
            ],
        ];
    }
}
