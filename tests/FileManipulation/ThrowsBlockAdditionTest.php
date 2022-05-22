<?php

namespace Psalm\Tests\FileManipulation;

class ThrowsBlockAdditionTest extends FileManipulationTestCase
{
    /**
     * @return array<string,array{string,string,string,string[],bool}>
     */
    public function providerValidCodeParse(): array
    {
        return [
            'addThrowsAnnotationToFunction' => [
                '<?php
                    function foo(string $s): string {
                        if("" === $s) {
                            throw new \InvalidArgumentException();
                        }
                        return $s;
                    }',
                '<?php
                    /**
                     * @throws InvalidArgumentException
                     */
                    function foo(string $s): string {
                        if("" === $s) {
                            throw new \InvalidArgumentException();
                        }
                        return $s;
                    }',
                '7.4',
                ['MissingThrowsDocblock'],
                true,
            ],
            'addMultipleThrowsAnnotationToFunction' => [
                '<?php
                    function foo(string $s): string {
                        if("" === $s) {
                            throw new \InvalidArgumentException();
                        }
                        if("" === \trim($s)) {
                            throw new \DomainException();
                        }
                        return $s;
                    }',
                '<?php
                    /**
                     * @throws InvalidArgumentException|DomainException
                     */
                    function foo(string $s): string {
                        if("" === $s) {
                            throw new \InvalidArgumentException();
                        }
                        if("" === \trim($s)) {
                            throw new \DomainException();
                        }
                        return $s;
                    }',
                '7.4',
                ['MissingThrowsDocblock'],
                true,
            ],
            'preservesExistingThrowsAnnotationToFunction' => [
                '<?php
                    /**
                     * @throws InvalidArgumentException|DomainException
                     */
                    function foo(string $s): string {
                        if("" === $s) {
                            throw new \Exception();
                        }
                        return $s;
                    }',
                '<?php
                    /**
                     * @throws InvalidArgumentException|DomainException
                     * @throws Exception
                     */
                    function foo(string $s): string {
                        if("" === $s) {
                            throw new \Exception();
                        }
                        return $s;
                    }',
                '7.4',
                ['MissingThrowsDocblock'],
                true,
            ],
        ];
    }
}
