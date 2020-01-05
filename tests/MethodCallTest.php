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

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
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
                        public function __call(string $method_name, array $args) {}
                    }

                    $a = new A;
                    $a->bar();',
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
                        if ($a->foo($b = (int) 5)) {
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
                    /** @return array<string,scalar> */
                    function fetch_assoc() : array {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetch(PDO::FETCH_ASSOC);
                    }'
            ],
            'pdoStatementFetchBoth' => [
                '<?php
                    /** @return array<scalar> */
                    function fetch_both() : array {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetch(PDO::FETCH_BOTH);
                    }'
            ],
            'pdoStatementFetchBound' => [
                '<?php
                    /** @return true */
                    function fetch_both() : bool {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetch(PDO::FETCH_BOUND);
                    }'
            ],
            'pdoStatementFetchClass' => [
                '<?php
                    /** @return object */
                    function fetch_class() : object {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetch(PDO::FETCH_CLASS);
                    }'
            ],
            'pdoStatementFetchLazy' => [
                '<?php
                    /** @return object */
                    function fetch_lazy() : object {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetch(PDO::FETCH_LAZY);
                    }'
            ],
            'pdoStatementFetchNamed' => [
                '<?php
                    /** @return array<string,scalar|list<scalar>> */
                    function fetch_named() : array {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetch(PDO::FETCH_NAMED);
                    }'
            ],
            'pdoStatementFetchNum' => [
                '<?php
                    /** @return list<scalar> */
                    function fetch_named() : array {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetch(PDO::FETCH_NUM);
                    }'
            ],
            'pdoStatementFetchObj' => [
                '<?php
                    /** @return stdClass */
                    function fetch_named() : object {
                        $p = new PDO("sqlite::memory:");
                        $sth = $p->prepare("SELECT 1");
                        $sth->execute();
                        return $sth->fetch(PDO::FETCH_OBJ);
                    }'
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse()
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
                            return self::mock("NullableClass");
                        }
                    }',
                'error_message' => 'LessSpecificReturnStatement',
                'error_levels' => ['MixedInferredReturnType', 'MixedReturnStatement', 'TypeCoercion', 'MixedMethodCall'],
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
        ];
    }
}
