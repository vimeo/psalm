<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Context;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class MethodCallTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function testExtendDocblockParamType(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
                new SoapFault("1", "faultstring", "faultactor");',
        );

        $this->analyzeFile('somefile.php', new Context());
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
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
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
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
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
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
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
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
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
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function providerValidCodeParse(): iterable
    {
        return [
            'notInCallMapTest' => [
                'code' => '<?php
                    new DOMImplementation();',
            ],
            'parentStaticCall' => [
                'code' => '<?php
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
                'code' => '<?php
                    class Foo {
                        public static function barBar(): void {}
                    }

                    (new Foo())->barBar();',
            ],
            'staticInvocation' => [
                'code' => '<?php
                    class A {
                        public static function fooFoo(): void {}
                    }

                    class B extends A {

                    }

                    B::fooFoo();',
            ],
            'staticCallOnVar' => [
                'code' => '<?php
                    class A {
                        public static function bar(): int {
                            return 5;
                        }
                    }
                    $foo = new A;
                    $b = $foo::bar();',
            ],
            'uppercasedSelf' => [
                'code' => '<?php
                    class X33{
                        public static function main(): void {
                            echo SELF::class . "\n";  // Class or interface SELF does not exist
                        }
                    }
                    X33::main();',
            ],
            'dateTimeImmutableStatic' => [
                'code' => '<?php
                    final class MyDate extends DateTimeImmutable {}

                    $today = new MyDate();
                    $yesterday = $today->sub(new DateInterval("P1D"));

                    $b = (new DateTimeImmutable())->modify("+3 hours");',
                'assertions' => [
                    '$yesterday' => 'MyDate',
                    '$b' => 'DateTimeImmutable',
                ],
            ],
            'magicCall' => [
                'code' => '<?php
                    class A {
                        public function __call(string $method_name, array $args) : string {
                            return "hello";
                        }
                    }

                    $a = new A;
                    $s = $a->bar();',
                'assertions' => [
                    '$s' => 'string',
                ],
            ],
            'canBeCalledOnMagic' => [
                'code' => '<?php
                    class A {
                      public function __call(string $method, array $args) {}
                    }

                    class B {}

                    $a = rand(0, 1) ? new A : new B;

                    $a->maybeUndefinedMethod();',
                'assertions' => [],
                'ignored_issues' => ['PossiblyUndefinedMethod'],
            ],
            'canBeCalledOnMagicWithMethod' => [
                'code' => '<?php
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
                'code' => '<?php
                    class A {
                        public function __invoke(string $p): void {}
                    }

                    $q = new A;
                    $q("asda");',
            ],
            'domDocumentAppendChild' => [
                'code' => '<?php
                    $doc = new DOMDocument("1.0");
                    $node = $doc->createElement("foo");
                    if ($node instanceof DOMElement) {
                        $newnode = $doc->appendChild($node);
                        $newnode->setAttribute("bar", "baz");
                    }',
            ],
            'nonStaticSelfCall' => [
                'code' => '<?php
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
                'code' => '<?php
                    $xml = new SimpleXMLElement("<a><b></b></a>");
                    $a = $xml->asXML();
                    $b = $xml->asXML("foo.xml");',
                'assertions' => [
                    '$a' => 'false|string',
                    '$b' => 'bool',
                ],
            ],
            'datetimeformatNotFalse' => [
                'code' => '<?php
                    $format = random_bytes(10);
                    $dt = new DateTime;
                    $formatted = $dt->format($format);
                    if (false !== $formatted) {}
                    function takesString(string $s) : void {}
                    takesString($formatted);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' =>  '7.4',
            ],
            'domElement' => [
                'code' => '<?php
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
                'code' => '<?php
                    /** @param non-empty-string $XML */
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
                'code' => '<?php
                    function getTypeName(ReflectionParameter $parameter): string {
                        $type = $parameter->getType();

                        if ($type === null) {
                            return "mixed";
                        }

                        if ($type instanceof ReflectionUnionType) {
                            return "union";
                        }

                        if ($type instanceof ReflectionNamedType) {
                            return $type->getName();
                        }

                        throw new RuntimeException("unexpected type");
                    }',
                    'assertions' => [],
                    'ignored_issues' => [],
                    'php_version' =>  '8.0',
            ],
            'PDOMethod' => [
                'code' => '<?php
                    function md5_and_reverse(string $string) : string {
                        return strrev(md5($string));
                    }

                    $db = new PDO("sqlite:sqlitedb");
                    $db->sqliteCreateFunction("md5rev", "md5_and_reverse", 1);',
            ],
            'dontConvertedMaybeMixedAfterCall' => [
                'code' => '<?php
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
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment', 'MixedMethodCall'],
            ],
            'methodResolution' => [
                'code' => '<?php
                    interface ReturnsString {
                        public function getId(): string;
                    }

                    /**
                     * @param mixed $a
                     */
                    function foo(ReturnsString $user, $a): string {
                        strlen($user->getId());

                        (is_object($a) && method_exists($a, "getS")) ? (string)$a->GETS() : "";

                        return $user->getId();
                    }',
            ],
            'defineVariableCreatedInArgToMixed' => [
                'code' => '<?php
                    function bar($a) : void {
                        if ($a->foo($b = (int) "5")) {
                            echo $b;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedMethodCall', 'MissingParamType'],
            ],
            'staticCallAfterMethodExists' => [
                'code' => '<?php
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
                'code' => '<?php
                    class Foo {
                        /** @var self */
                        public static $current;
                        public function bar() : void {}
                    }

                    Foo::$current->bar();',
            ],
            'pdoStatementSetFetchMode' => [
                'code' => '<?php
                    class A {
                        /** @var ?string */
                        public $a;
                    }
                    class B extends A {}

                    $db = new PDO("sqlite::memory:");
                    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    $stmt = $db->prepare("select \"a\" as a");
                    $stmt->setFetchMode(PDO::FETCH_CLASS, A::class);
                    $stmt2 = $db->prepare("select \"a\" as a");
                    $stmt2->setFetchMode(PDO::FETCH_ASSOC);
                    $stmt3 = $db->prepare("select \"a\" as a");
                    $stmt3->setFetchMode(PDO::ATTR_DEFAULT_FETCH_MODE);
                    $stmt->execute();
                    $stmt2->execute();
                    /** @psalm-suppress MixedAssignment */
                    $a = $stmt->fetch();
                    $b = $stmt->fetchAll();
                    $c = $stmt->fetch(PDO::FETCH_CLASS);
                    $d = $stmt->fetchAll(PDO::FETCH_CLASS);
                    $e = $stmt->fetchAll(PDO::FETCH_CLASS, B::class);
                    $f = $stmt->fetch(PDO::FETCH_ASSOC);
                    $g = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    /** @psalm-suppress MixedAssignment */
                    $h = $stmt2->fetch();
                    $i = $stmt2->fetchAll();
                    $j = $stmt2->fetch(PDO::FETCH_BOTH);
                    $k = $stmt2->fetchAll(PDO::FETCH_BOTH);
                    /** @psalm-suppress MixedAssignment */
                    $l = $stmt3->fetch();',
                'assertions' => [
                    '$a' => 'mixed',
                    '$b' => 'array<array-key, mixed>|false',
                    '$c' => 'false|object',
                    '$d' => 'list<object>',
                    '$e' => 'list<B>',
                    '$f' => 'array<string, null|scalar>|false',
                    '$g' => 'list<array<string, null|scalar>>',
                    '$h' => 'mixed',
                    '$i' => 'array<array-key, mixed>|false',
                    '$j' => 'array<array-key, null|scalar>|false',
                    '$k' => 'list<array<array-key, null|scalar>>',
                    '$l' => 'mixed',
                ],
            ],
            'datePeriodConstructor' => [
                'code' => '<?php
                    function foo(DateTime $d1, DateTime $d2) : void {
                        new DatePeriod(
                            $d1,
                            DateInterval::createFromDateString("1 month"),
                            $d2
                        );
                    }',
            ],
            'methodExistsDoesntExhaustMemory' => [
                'code' => '<?php
                    class C {}

                    function f(C $c): void {
                        method_exists($c, \'a\') ? $c->a() : [];
                        method_exists($c, \'b\') ? $c->b() : [];
                        method_exists($c, \'c\') ? $c->c() : [];
                        method_exists($c, \'d\') ? $c->d() : [];
                        method_exists($c, \'e\') ? $c->e() : [];
                        method_exists($c, \'f\') ? $c->f() : [];
                        method_exists($c, \'g\') ? $c->g() : [];
                        method_exists($c, \'h\') ? $c->h() : [];
                        method_exists($c, \'i\') ? $c->i() : [];
                    }',
            ],
            'callMethodAfterCheckingExistence' => [
                'code' => '<?php
                    class A {}

                    function foo(A $a) : void {
                        if (method_exists($a, "bar")) {
                            /** @psalm-suppress MixedArgument */
                            echo $a->bar();
                        }
                    }',
            ],
            'callMethodAfterCheckingExistenceInClosure' => [
                'code' => '<?php
                    class A {}

                    function foo(A $a) : void {
                        if (method_exists($a, "bar")) {
                            (function() use ($a) : void {
                                /** @psalm-suppress MixedArgument */
                                echo $a->bar();
                            })();

                        }
                    }',
            ],
            'callManyMethodsAfterCheckingExistence' => [
                'code' => '<?php
                    function foo(object $object) : void {
                        if (!method_exists($object, "foo")) {
                            return;
                        }
                        if (!method_exists($object, "bar")) {
                            return;
                        }
                        $object->foo();
                        $object->bar();
                    }',
            ],
            'callManyMethodsAfterCheckingExistenceChained' => [
                'code' => '<?php
                    function foo(object $object) : void {
                        if (method_exists($object, "foo") && method_exists($object, "bar")) {
                            $object->foo();
                            $object->bar();
                        }
                    }',
            ],
            'callManyMethodsOnKnownObjectAfterCheckingExistenceChained' => [
                'code' => '<?php
                    class A {}
                    function foo(A $object) : void {
                        if (method_exists($object, "foo") && method_exists($object, "bar")) {
                            $object->foo();
                            $object->bar();
                        }
                    }',
            ],
            'preserveMethodExistsType' => [
                'code' => '<?php
                    /**
                     * @param class-string $foo
                     */
                    function foo(string $foo): string {
                        if (!method_exists($foo, "something")) {
                            return "";
                        }

                        return $foo;
                    }',
            ],
            'methodDoesNotExistOnClass' => [
                'code' => '<?php
                    class A {}

                    /**
                     * @param class-string<A> $foo
                     */
                    function foo(string $foo): string {
                        if (!method_exists($foo, "something")) {
                            return "";
                        }

                        return $foo;
                    }',
            ],
            'pdoStatementFetchColumn' => [
                'code' => '<?php
                    /** @return scalar|null|false */
                    function fetch_column() {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetch(PDO::FETCH_COLUMN);
                    }',
            ],
            'pdoStatementFetchAllColumn' => [
                'code' => '<?php
                    /** @return list<scalar|null> */
                    function fetch_column() {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetchAll(PDO::FETCH_COLUMN);
                    }',
            ],
            'pdoStatementFetchKeyPair' => [
                'code' => '<?php
                    /** @return array<array-key,scalar|null> */
                    function fetch_column() {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetch(PDO::FETCH_KEY_PAIR);
                    }',
            ],
            'pdoStatementFetchAllKeyPair' => [
                'code' => '<?php
                    /** @return array<array-key,scalar|null> */
                    function fetch_column() {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetchAll(PDO::FETCH_KEY_PAIR);
                    }',
            ],
            'pdoStatementFetchAssoc' => [
                'code' => '<?php
                    /** @return array<string,null|scalar>|false */
                    function fetch_assoc() {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetch(PDO::FETCH_ASSOC);
                    }',
            ],
            'pdoStatementFetchAllAssoc' => [
                'code' => '<?php
                    /** @return list<array<string,null|scalar>> */
                    function fetch_assoc() : array {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetchAll(PDO::FETCH_ASSOC);
                    }',
            ],
            'pdoStatementFetchBoth' => [
                'code' => '<?php
                    /** @return array<null|scalar>|false */
                    function fetch_both() {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetch(PDO::FETCH_BOTH);
                    }',
            ],
            'pdoStatementFetchAllBoth' => [
                'code' => '<?php
                    /** @return list<array<null|scalar>> */
                    function fetch_both() : array {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetchAll(PDO::FETCH_BOTH);
                    }',
            ],
            'pdoStatementFetchBound' => [
                'code' => '<?php
                    /** @return bool */
                    function fetch_both() : bool {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetch(PDO::FETCH_BOUND);
                    }',
            ],
            'pdoStatementFetchAllBound' => [
                'code' => '<?php
                    /** @return list<bool> */
                    function fetch_both() : array {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetchAll(PDO::FETCH_BOUND);
                    }',
            ],
            'pdoStatementFetchClass' => [
                'code' => '<?php
                    /** @return object|false */
                    function fetch_class() {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetch(PDO::FETCH_CLASS);
                    }',
            ],
            'pdoStatementFetchAllClass' => [
                'code' => '<?php
                    /** @return list<object> */
                    function fetch_class() : array {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetchAll(PDO::FETCH_CLASS);
                    }',
            ],
            'pdoStatementFetchAllNamedClass' => [
                'code' => '<?php
                    class Foo {}

                    /** @return list<Foo> */
                    function fetch_class() : array {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetchAll(PDO::FETCH_CLASS, Foo::class);
                    }',
            ],
            'pdoStatementFetchLazy' => [
                'code' => '<?php
                    /** @return object|false */
                    function fetch_lazy() {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetch(PDO::FETCH_LAZY);
                    }',
            ],
            'pdoStatementFetchNamed' => [
                'code' => '<?php
                    /** @return array<string,scalar|null|list<scalar|null>>|false */
                    function fetch_named() {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetch(PDO::FETCH_NAMED);
                    }',
            ],
            'pdoStatementFetchAllNamed' => [
                'code' => '<?php
                    /** @return list<array<string,scalar|null|list<scalar|null>>> */
                    function fetch_named() : array {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetchAll(PDO::FETCH_NAMED);
                    }',
            ],
            'pdoStatementFetchNum' => [
                'code' => '<?php
                    /** @return list<null|scalar>|false */
                    function fetch_named() {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetch(PDO::FETCH_NUM);
                    }',
            ],
            'pdoStatementFetchAllNum' => [
                'code' => '<?php
                    /** @return list<list<null|scalar>> */
                    function fetch_named() : array {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetchAll(PDO::FETCH_NUM);
                    }',
            ],
            'pdoStatementFetchObj' => [
                'code' => '<?php
                    /** @return stdClass|false */
                    function fetch_named() {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetch(PDO::FETCH_OBJ);
                    }',
            ],
            'pdoStatementFetchAllObj' => [
                'code' => '<?php
                    /** @return list<stdClass> */
                    function fetch_named() : array {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetchAll(PDO::FETCH_OBJ);
                    }',
            ],
            'dateTimeSecondArg' => [
                'code' => '<?php
                    $date = new DateTime(null, new DateTimeZone("Pacific/Nauru"));
                    echo $date->format("Y-m-d H:i:sP") . "\n";',
            ],
            'noCrashOnGetClassMethodCallWithNull' => [
                'code' => '<?php
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
                'code' => '<?php
                    class A {
                        private string $b = "c";

                        public function passesByRef(object $a): void {
                            /** @psalm-suppress MixedMethodCall */
                            $a->passedByRef($this->b);
                        }
                    }',
            ],
            'maybeNotTooManyArgumentsToInstance' => [
                'code' => '<?php
                    class A {
                        public function fooFoo(int $a): void {}
                    }

                    class B {
                        public function fooFoo(int $a, string $s): void {}
                    }

                    (rand(0, 1) ? new A : new B)->fooFoo(5, "dfd");',
            ],
            'interfaceMethodCallCheck' => [
                'code' => '<?php
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

                    takesWithoutArguments(new C);',
            ],
            'getterAutomagicAssertion' => [
                'code' => '<?php
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
                    }',
            ],
            'ignorePossiblyNull' => [
                'code' => '<?php
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
                    }',
            ],
            'abstractMethodExistsOnChild' => [
                'code' => '<?php
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
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'pdoQueryTwoArgs' => [
                'code' => '<?php
                    $pdo = new PDO("test");
                    $pdo->query("SELECT * FROM projects", PDO::FETCH_NAMED);',
            ],
            'unchainedInferredMutationFreeMethodCallMemoize' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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

                    if ($main->getProperty() !== null && $main->getProperty()->test()) {}',
            ],
            'getterTypeInferring' => [
                'code' => '<?php
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
                'code' => '<?php
                    $a = new SplObjectStorage();',
                'assertions' => [
                    '$a' => 'SplObjectStorage<never, never>',
                ],
            ],
            'allowIteratorToBeNull' => [
                'code' => '<?php
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

                    if ($it->current() === null) {}',
            ],
            'resolveFinalInParentCall' => [
                'code' => '<?php
                    abstract class A {
                        protected static function create() : static {
                            return new static();
                        }

                        final private function __construct() {}
                    }

                    final class B extends A {
                        public static function new() : self {
                            return parent::create();
                        }
                    }',
            ],
            'noCrashWhenCallingParent' => [
                'code' => '<?php
                    namespace FooBar;

                    class Datetime extends \DateTime
                    {
                        public static function createFromInterface(\DateTimeInterface $datetime): static
                        {
                            return parent::createFromInterface($datetime);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedReturnStatement', 'MixedInferredReturnType'],
                'php_version' => '8.0',
            ],
            'nullsafeShortCircuit' => [
                'code' => '<?php
                    interface Bar {
                        public function doBaz(): void;
                    }
                    interface Foo {
                        public function getBar(): Bar;
                    }
                    function fooOrNull(): ?Foo {
                        return null;
                    }
                    fooOrNull()?->getBar()->doBaz();',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'parentMagicMethodCall' => [
                'code' => '<?php
                    class Model {
                        /**
                         * @return static
                         */
                        public function __call(string $method, array $args) {
                            /** @psalm-suppress UnsafeInstantiation */
                            return new static;
                        }
                    }

                    class BlahModel extends Model {
                        /**
                         * @param mixed $input
                         */
                        public function create($input): BlahModel
                        {
                            return parent::create([]);
                        }
                    }

                    $m = new BlahModel();
                    $n = $m->create([]);',
                'assertions' => [
                    '$n' => 'BlahModel',
                ],
            ],
            'methodLevelGenericsWillBeInherited' => [
                'code' => '<?php
                    interface I
                    {
                        /**
                         * @template TResult
                         * @param TResult $value
                         * @return TResult
                         */
                        public function method(mixed $value): mixed;
                    }
                    final class A implements I
                    {
                        public function method(mixed $value): mixed
                        {
                            return $value;
                        }
                    }
                    $_v = (new A)->method("a");
                    /** @psalm-check-type-exact $_v = "a" */',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'phpdocObjectTypeAndReferenceInParameter' => [
                'code' => '<?php
                    class Foo {
                        /**
                         * @param object $object
                         */
                        public function bar(&$object): void {}
                    }
                    $x = new Foo();
                    $x->bar($x);',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'staticInvocation' => [
                'code' => '<?php
                    class Foo {
                        public function barBar(): void {}
                    }

                    Foo::barBar();',
                'error_message' => 'InvalidStaticInvocation',
            ],
            'parentStaticCall' => [
                'code' => '<?php
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
                'code' => '<?php
                    class Foo {
                        public static function barBar(): void {}
                    }

                    /** @var mixed */
                    $a = (new Foo());

                    $a->barBar();',
                'error_message' => 'MixedMethodCall',
                'ignored_issues' => [
                    'MissingPropertyType',
                    'MixedAssignment',
                ],
            ],
            'invalidMethodCall' => [
                'code' => '<?php
                    ("hello")->someMethod();',
                'error_message' => 'InvalidMethodCall',
            ],
            'possiblyInvalidMethodCall' => [
                'code' => '<?php
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
                'code' => '<?php
                    class A {
                        public function fooFoo(): void {}

                        public static function barBar(): void {
                            self::fooFoo();
                        }
                    }',
                'error_message' => 'NonStaticSelfCall',
            ],
            'noParent' => [
                'code' => '<?php
                    class Foo {
                        public function barBar(): void {
                            parent::barBar();
                        }
                    }',
                'error_message' => 'ParentNotFound',
            ],
            'coercedClass' => [
                'code' => '<?php
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
                'ignored_issues' => ['MixedInferredReturnType', 'MixedReturnStatement', 'MixedMethodCall'],
            ],
            'undefinedVariableStaticCall' => [
                'code' => '<?php
                    $foo::bar();',
                'error_message' => 'UndefinedGlobalVariable',
            ],
            'staticCallOnString' => [
                'code' => '<?php
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
                'code' => '<?php
                    $this->foo();',
                'error_message' => 'InvalidScope',
            ],
            'possiblyFalseReference' => [
                'code' => '<?php
                    class A {
                        public function bar(): void {}
                    }

                    $a = rand(0, 1) ? new A : false;
                    $a->bar();',
                'error_message' => 'PossiblyFalseReference',
            ],
            'undefinedParentClass' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress UndefinedClass
                     */
                    class B extends A {}

                    $b = new B();',
                'error_message' => 'MissingDependency - src' . DIRECTORY_SEPARATOR . 'somefile.php:7',
            ],
            'variableMethodCallOnArray' => [
                'code' => '<?php
                    $arr = [];
                    $b = "foo";
                    $arr->$b();',
                'error_message' => 'InvalidMethodCall',
            ],
            'intVarStaticCall' => [
                'code' => '<?php
                    $a = 5;
                    $a::bar();',
                'error_message' => 'UndefinedClass',
            ],
            'intVarNewCall' => [
                'code' => '<?php
                    $a = 5;
                    new $a();',
                'error_message' => 'UndefinedClass',
            ],
            'invokeTypeMismatch' => [
                'code' => '<?php
                    class A {
                        public function __invoke(string $p): void {}
                    }

                    $q = new A;
                    $q(1);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'explicitInvokeTypeMismatch' => [
                'code' => '<?php
                    class A {
                        public function __invoke(string $p): void {}
                    }
                    (new A)->__invoke(1);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'undefinedMethodPassedAsArg' => [
                'code' => '<?php
                    class A {
                        public function __call(string $method, array $args) {}
                    }

                    $q = new A;
                    $q->foo(bar());',
                'error_message' => 'UndefinedFunction',
            ],
            'noIntersectionMethod' => [
                'code' => '<?php
                    interface A {}
                    interface B {}

                    /** @param B&A $p */
                    function f($p): void {
                        $p->zugzug();
                    }',
                'error_message' => 'UndefinedInterfaceMethod - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:29 - Method (B&A)::zugzug does not exist',
            ],
            'noInstanceCallAsStatic' => [
                'code' => '<?php
                    class C {
                        public function foo() : void {}
                    }

                    (new C)::foo();',
                'error_message' => 'InvalidStaticInvocation',
            ],
            'noExceptionOnMissingClass' => [
                'code' => '<?php
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
                'error_message' => 'MissingConstructor',
            ],
            'checkMixedMethodCallStaticMethodCallArg' => [
                'code' => '<?php
                    class B {}
                    /** @param mixed $a */
                    function foo($a) : void {
                        /** @psalm-suppress MixedMethodCall */
                        $a->bar(B::bat());
                    }',
                'error_message' => 'UndefinedMethod',
            ],
            'complainAboutUndefinedPropertyOnMixedCall' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    class A {
                        public function foo(): void {}
                    }

                    $p = new A();
                    $p->foo()->bar();',
                'error_message' => 'NullReference',
            ],
            'dateTimeNullFirstArg' => [
                'code' => '<?php
                    $date = new DateTime(null);',
                'error_message' => 'NullArgument',
            ],
            'noCrashOnGetClassMethodCall' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    class A {
                        public static function fooFoo(int $a): void {}
                    }

                    A::fooFoo(5, "dfd");',
                'error_message' => 'TooManyArguments',
            ],
            'tooFewArgumentsToStatic' => [
                'code' => '<?php
                    class A {
                        public static function fooFoo(int $a): void {}
                    }

                    A::fooFoo();',
                'error_message' => 'TooFewArguments',
            ],
            'tooManyArgumentsToInstance' => [
                'code' => '<?php
                    class A {
                        public function fooFoo(int $a): void {}
                    }

                    (new A)->fooFoo(5, "dfd");',
                'error_message' => 'TooManyArguments',
            ],
            'tooFewArgumentsToInstance' => [
                'code' => '<?php
                    class A {
                        public function fooFoo(int $a): void {}
                    }

                    (new A)->fooFoo();',
                'error_message' => 'TooFewArguments',
            ],
            'getterAutomagicOverridden' => [
                'code' => '<?php
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
                'error_message' => 'PossiblyNullArgument',
            ],
            'getterAutomagicOverriddenWithAssertion' => [
                'code' => '<?php
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
                'error_message' => 'PossiblyNullArgument',
            ],
            'checkVariableInUnknownClassConstructor' => [
                'code' => '<?php
                    /** @psalm-suppress UndefinedClass */
                    new Missing($class_arg);',
                'error_message' => 'PossiblyUndefinedVariable',
            ],
            'unchainedInferredInferredMutationFreeMethodCallDontMemoize' => [
                'code' => '<?php
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
                'code' => '<?php
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
            'possiblyNullReferenceInInvokedCall' => [
                'code' => '<?php
                    interface Location {
                        public function getId(): int;
                    }

                    /** @psalm-immutable */
                    interface Application {
                        public function getLocation(): ?Location;
                    }

                    interface TakesId {
                        public function __invoke(int $location): int;
                    }

                    function f(TakesId $takesId, Application $application): void {
                       ($takesId)($application->getLocation()->getId());
                    }',
                'error_message' => 'PossiblyNullReference',
            ],
            'nullsafeShortCircuitInVariable' => [
                'code' => '<?php
                    interface Bar {
                        public function doBaz(): void;
                    }
                    interface Foo {
                        public function getBar(): Bar;
                    }
                    function fooOrNull(): ?Foo {
                        return null;
                    }
                    $a = fooOrNull()?->getBar();
                    $a->doBaz();',
                'error_message' => 'PossiblyNullReference',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'undefinedMethodOnParentCallWithMethodExistsOnSelf' => [
                'code' => '<?php
                    class A {}
                    class B extends A {
                        public function foo(): string {
                            return parent::foo();
                        }
                    }',
                'error_message' => 'UndefinedMethod',
            ],
            'incorrectCallableParamDefault' => [
                'code' => '<?php
                    class A {
                        public function foo(callable $_a = "strlen"): void {}
                    }
                ',
                'error_message' => 'InvalidParamDefault',
            ],
        ];
    }
}
