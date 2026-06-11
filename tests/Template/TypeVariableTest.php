<?php

declare(strict_types=1);

namespace Psalm\Tests\Template;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class TypeVariableTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'unboundConstructorTemplate' => [
                'code' => '<?php
                    /** @template T of int|string */
                    class Box {
                        public function __construct() {}

                        /** @param T $item */
                        public function add($item): void {}
                    }

                    function good(): void {
                        $box = new Box();
                        $box->add(1);
                        $box->add("two");
                    }

                    /** @template T */
                    class Holder {
                        public function __construct() {}
                    }

                    function passesThrough(): Holder {
                        return new Holder();
                    }',
            ],
            'constructorBoundWidening' => [
                'code' => '<?php
                    /**
                     * @template T of int|string
                     */
                    class Box {
                        /** @param T $t */
                        public function __construct(public $t) {}
                        /** @param T $item */
                        public function set($item): void {
                            $this->t = $item;
                        }
                    }

                    function good(): Box {
                        $box = new Box(1);
                        $box->set("two");
                        return $box;
                    }',
            ],
            'boundViolationSuppressed' => [
                'code' => '<?php
                    /** @template T of int */
                    class IntBox {
                        public function __construct() {}

                        /** @param T $item */
                        public function add($item): void {}
                    }

                    /** @psalm-suppress IncompatibleTypeParameters */
                    function probe(): void {
                        $box = new IntBox();
                        $box->add("nope");
                    }',
            ],
        ];
    }

    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'typeVariableBoundViolation' => [
                'code' => '<?php
                    /** @template T of int */
                    class IntBox {
                        public function __construct() {}

                        /** @param T $item */
                        public function add($item): void {}
                    }

                    function probe(): void {
                        $box = new IntBox();
                        $box->add("nope");
                    }',
                'error_message' => 'IncompatibleTypeParameters - src' . DIRECTORY_SEPARATOR
                    . "somefile.php:12:35 - Type 'nope' should be a subtype of int",
            ],
            'constructorBoundConflictsWithDeclaredReturn' => [
                'code' => '<?php
                    /**
                     * @template T of int|string
                     */
                    class Box {
                        /** @param T $t */
                        public function __construct(public $t) {}
                        /** @param T $item */
                        public function set($item): void {
                            $this->t = $item;
                        }
                    }

                    /** @return Box<string> */
                    function bad(): Box {
                        $box = new Box(1);
                        $box->set("two");
                        return $box;
                    }',
                'error_message' => 'IncompatibleTypeParameters - src' . DIRECTORY_SEPARATOR
                    . "somefile.php:16:32 - Type 1 should be a subtype of string",
            ],
            'constructorBoundWideningBeyondConstraint' => [
                'code' => '<?php
                    /**
                     * @template T of int|string
                     */
                    class Box {
                        /** @param T $t */
                        public function __construct(public $t) {}
                        /** @param T $item */
                        public function set($item): void {
                            $this->t = $item;
                        }
                    }

                    function bad(): void {
                        $box = new Box(1);
                        $box->set(new DateTime());
                    }',
                'error_message' => 'IncompatibleTypeParameters - src' . DIRECTORY_SEPARATOR
                    . "somefile.php:16:35 - Type DateTime should be a subtype of int|string",
            ],
            'globalScopeBoundViolation' => [
                'code' => '<?php
                    /** @template T of int */
                    class IntBox {
                        public function __construct() {}

                        /** @param T $item */
                        public function add($item): void {}
                    }

                    $box = new IntBox();
                    $box->add("nope");',
                'error_message' => 'IncompatibleTypeParameters - src' . DIRECTORY_SEPARATOR
                    . "somefile.php:11:31 - Type 'nope' should be a subtype of int",
            ],
        ];
    }
}
