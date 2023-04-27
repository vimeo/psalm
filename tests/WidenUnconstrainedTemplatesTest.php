<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;

final class WidenUnconstrainedTemplatesTest extends TestCase
{
    public function testLiteralWillBeWidenIfTemplateHasNoConstraints(): void
    {
        Config::getInstance()->widen_unconstrained_templates = true;

        $this->addFile(
            'somefile.php',
            '<?php
              /** @template T */
              final class Container {
                  /** @param T $value */
                  public function __construct($value) {}
              }

              /** @var list<1|2|3> */
              $list = [];
              /** @var array<1|2|3, 4|5|6> */
              $array = [];
              /** @var non-empty-array<1|2|3, 4|5|6> */
              $nonEmptyArray = [1 => 5];
              /** @var ArrayObject<"fst", 1> */
              $arrayObject = new ArrayObject(["fst" => 1]);
              /** @var Countable&iterable<"snd", 2> */
              $countableAndIterable = new ArrayObject(["snd" => 2]);
              $objectWithProperties = (object)["a" => 42];

              $containerInt = new Container(42);
              /** @psalm-check-type-exact $containerInt = Container<int> */

              $containerFloat = new Container(42.00);
              /** @psalm-check-type-exact $containerFloat = Container<float> */

              $containerBool = new Container(true);
              /** @psalm-check-type-exact $containerBool = Container<bool> */

              $containerString = new Container("");
              /** @psalm-check-type-exact $containerString = Container<string> */

              $containerNonEmptyString = new Container("str");
              /** @psalm-check-type-exact $containerNonEmptyString = Container<non-empty-string> */

              $containerArrayIntInt = new Container($array);
              /** @psalm-check-type-exact $containerArrayIntInt = Container<array<int, int>> */

              $containerNonEmptyArrayIntInt = new Container($nonEmptyArray);
              /** @psalm-check-type-exact $containerNonEmptyArrayIntInt = Container<non-empty-array<int, int>> */

              $containerListInt = new Container($list);
              /** @psalm-check-type-exact $containerListInt = Container<list<int>> */

              $containerNonEmptyListInt = new Container([1, 2, 3]);
              /** @psalm-check-type-exact $containerNonEmptyListInt = Container<non-empty-list<int>> */

              $containerArrayObject = new Container($arrayObject);
              /** @psalm-check-type-exact $containerArrayObject = Container<ArrayObject<non-empty-string, int>> */

              $containerCountableAndIterable = new Container($countableAndIterable);
              /** @psalm-check-type-exact $containerCountableAndIterable = Container<Countable&iterable<non-empty-string, int>> */

              $containerObjectWithProperties = new Container($objectWithProperties);
              /** @psalm-check-type-exact $containerObjectWithProperties = Container<object{a: int}> */',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testLiteralWillBeNarrowedIfTemplateHasAnyConstraints(): void
    {
        Config::getInstance()->widen_unconstrained_templates = true;

        $this->addFile(
            'somefile.php',
            '<?php
              /** @template T of mixed|null */
              final class Container {
                  /** @param T $value */
                  public function __construct($value) {}
              }

              /** @var list<1|2|3> */
              $list = [];
              /** @var array<1|2|3, 4|5|6> */
              $array = [];
              /** @var non-empty-array<1|2|3, 4|5|6> */
              $nonEmptyArray = [1 => 5];
              /** @var ArrayObject<"fst", 1> */
              $arrayObject = new ArrayObject(["fst" => 1]);
              /** @var Countable&iterable<"snd", 2> */
              $countableAndIterable = new ArrayObject(["snd" => 2]);
              $objectWithProperties = (object)["a" => 42];

              $containerInt = new Container(42);
              /** @psalm-check-type-exact $containerInt = Container<42> */

              $containerFloat = new Container(42.00);
              /** @psalm-check-type-exact $containerFloat = Container<42.00> */

              $containerBool = new Container(true);
              /** @psalm-check-type-exact $containerBool = Container<true> */

              $containerString = new Container("");
              /** @psalm-check-type-exact $containerString = Container<\'\'> */

              $containerNonEmptyString = new Container("str");
              /** @psalm-check-type-exact $containerNonEmptyString = Container<\'str\'> */

              $containerArrayIntInt = new Container($array);
              /** @psalm-check-type-exact $containerArrayIntInt = Container<array<1|2|3, 4|5|6>> */

              $containerNonEmptyArrayIntInt = new Container($nonEmptyArray);
              /** @psalm-check-type-exact $containerNonEmptyArrayIntInt = Container<non-empty-array<1|2|3, 4|5|6>> */

              $containerListInt = new Container($list);
              /** @psalm-check-type-exact $containerListInt = Container<list<1|2|3>> */

              $containerNonEmptyListInt = new Container([1, 2, 3]);
              /** @psalm-check-type-exact $containerNonEmptyListInt = Container<list{1, 2, 3}> */

              $containerArrayObject = new Container($arrayObject);
              /** @psalm-check-type-exact $containerArrayObject = Container<ArrayObject<"fst", 1>> */

              $containerCountableAndIterable = new Container($countableAndIterable);
              /** @psalm-check-type-exact $containerCountableAndIterable = Container<Countable&iterable<"snd", 2>> */

              $containerObjectWithProperties = new Container($objectWithProperties);
              /** @psalm-check-type-exact $containerObjectWithProperties = Container<object{a: 42}> */',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testLiteralWillBeNarrowedWithExplicitAnnotationAtClass(): void
    {
        Config::getInstance()->widen_unconstrained_templates = true;

        $this->addFile(
            'somefile.php',
            '<?php
              /**
               * @template T
               * @psalm-narrow-unconstrained-templates
               */
              final class Container {
                  /** @param T $value */
                  public function __construct($value) {}
              }

              $containerInt = new Container(42);
              /** @psalm-check-type-exact $containerInt = Container<42> */',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testLiteralWillBeNarrowedWithExplicitAnnotationAtFunction(): void
    {
        Config::getInstance()->widen_unconstrained_templates = true;

        $this->addFile(
            'somefile.php',
            '<?php
              /**
               * @template T
               * @psalm-narrow-unconstrained-templates
               * @param T $value
               * @return T
               */
              function id($value) {
                  return $value;
              }

              $containerInt = id([42]);
              /** @psalm-check-type-exact $containerInt = list{42} */',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testLiteralWillBeNarrowedWithExplicitAnnotationAtMethod(): void
    {
        Config::getInstance()->widen_unconstrained_templates = true;

        $this->addFile(
            'somefile.php',
            '<?php
              final class Container {
                  /**
                   * @template T
                   * @psalm-narrow-unconstrained-templates
                   * @param T $value
                   * @return T
                   */
                  public function id($value) {
                      return $value;
                  }
              }

              $containerInt = (new Container())->id([42]);
              /** @psalm-check-type-exact $containerInt = list{42} */',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testLiteralWillBeNarrowedWithExplicitAnnotationAtStaticMethod(): void
    {
        Config::getInstance()->widen_unconstrained_templates = true;

        $this->addFile(
            'somefile.php',
            '<?php
              final class Container {
                  /**
                   * @template T
                   * @psalm-narrow-unconstrained-templates
                   * @param T $value
                   * @return T
                   */
                  public static function id($value) {
                      return $value;
                  }
              }

              $containerInt = Container::id([42]);
              /** @psalm-check-type-exact $containerInt = list{42} */',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testLiteralWillBeWidenWithExplicitAnnotationAtClass(): void
    {
        Config::getInstance()->widen_unconstrained_templates = false;

        $this->addFile(
            'somefile.php',
            '<?php
              /**
               * @template T
               * @psalm-widen-unconstrained-templates
               */
              final class Container {
                  /** @param T $value */
                  public function __construct($value) {}
              }

              $containerInt = new Container(42);
              /** @psalm-check-type-exact $containerInt = Container<int> */',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testLiteralWillBeWidenWithExplicitAnnotationAtFunction(): void
    {
        Config::getInstance()->widen_unconstrained_templates = false;

        $this->addFile(
            'somefile.php',
            '<?php
              /**
               * @template T
               * @psalm-widen-unconstrained-templates
               * @param T $value
               * @return T
               */
              function id($value) {
                  return $value;
              }

              $containerInt = id([42]);
              /** @psalm-check-type-exact $containerInt = non-empty-list<int> */',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testLiteralWillBeWidenWithExplicitAnnotationAtMethod(): void
    {
        Config::getInstance()->widen_unconstrained_templates = false;

        $this->addFile(
            'somefile.php',
            '<?php
              final class Container {
                  /**
                   * @template T
                   * @psalm-widen-unconstrained-templates
                   * @param T $value
                   * @return T
                   */
                  public function id($value) {
                      return $value;
                  }
              }

              $containerInt = (new Container())->id([42]);
              /** @psalm-check-type-exact $containerInt = non-empty-list<int> */',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testLiteralWillBeWidenWithExplicitAnnotationAtStaticMethod(): void
    {
        Config::getInstance()->widen_unconstrained_templates = false;

        $this->addFile(
            'somefile.php',
            '<?php
              final class Container {
                  /**
                   * @template T
                   * @psalm-widen-unconstrained-templates
                   * @param T $value
                   * @return T
                   */
                  public static function id($value) {
                      return $value;
                  }
              }

              $containerInt = Container::id([42]);
              /** @psalm-check-type-exact $containerInt = non-empty-list<int> */',
        );

        $this->analyzeFile('somefile.php', new Context());
    }
}
