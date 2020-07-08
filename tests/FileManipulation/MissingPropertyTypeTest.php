<?php
namespace Psalm\Tests\FileManipulation;

class MissingPropertyTypeTest extends FileManipulationTest
{
    /**
     * @return array<string,array{string,string,string,string[],bool,5?:bool}>
     */
    public function providerValidCodeParse()
    {
        return [
            'addMissingUnionType56' => [
                '<?php
                    class A {
                        public $v;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->v = 4;
                            } else {
                                $this->v = "hello";
                            }
                        }
                    }',
                '<?php
                    class A {
                        /**
                         * @var int|string
                         */
                        public $v;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->v = 4;
                            } else {
                                $this->v = "hello";
                            }
                        }
                    }',
                '5.6',
                ['MissingPropertyType'],
                true,
            ],
            'addMissingNullableType56' => [
                '<?php
                    class A {
                        public $v;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->v = 4;
                            }
                        }
                    }',
                '<?php
                    class A {
                        /**
                         * @var int|null
                         */
                        public $v;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->v = 4;
                            }
                        }
                    }',
                '5.6',
                ['MissingPropertyType'],
                true,
            ],
            'addMissingNullableTypeNoDefault74' => [
                '<?php
                    class A {
                        public $v;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->v = 4;
                            }
                        }
                    }',
                '<?php
                    class A {
                        /**
                         * @var int|null
                         */
                        public $v;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->v = 4;
                            }
                        }
                    }',
                '7.4',
                ['MissingPropertyType'],
                true,
            ],
            'addMissingNullableTypeWithDefault74' => [
                '<?php
                    class A {
                        public $v = null;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->v = 4;
                            }
                        }
                    }',
                '<?php
                    class A {
                        public ?int $v = null;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->v = 4;
                            }
                        }
                    }',
                '7.4',
                ['MissingPropertyType'],
                true,
            ],
            'addMissingUnionTypeSetInBranches74' => [
                '<?php
                    class A {
                        public $v;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->v = 4;
                            } else {
                                $this->v = "hello";
                            }
                        }
                    }',
                '<?php
                    class A {
                        /**
                         * @var int|string
                         */
                        public $v;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->v = 4;
                            } else {
                                $this->v = "hello";
                            }
                        }
                    }',
                '7.4',
                ['MissingPropertyType'],
                true,
            ],
            'addMissingIntTypeSetInBranches74' => [
                '<?php
                    class A {
                        public $v;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->v = 4;
                            } else {
                                $this->v = 20;
                            }
                        }
                    }',
                '<?php
                    class A {
                        public int $v;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->v = 4;
                            } else {
                                $this->v = 20;
                            }
                        }
                    }',
                '7.4',
                ['MissingPropertyType'],
                true,
            ],
        ];
    }
}
