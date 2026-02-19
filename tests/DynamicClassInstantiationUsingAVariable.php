<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;

final class DynamicClassInstantiationUsingAVariable extends TestCase
{
    /**
     * Reproduces crash from GitHub issue #11491.
     *
     * Psalm fails to handle dynamic class instantiation when the throwable
     * class is provided via a string variable, and the \Class or Class::class
     * is not mentioned parsable by AST elsewhere in the code.
     */
    public function testDynamicThrowableCrash(): void
    {
        /*
         * To make this a passing test case for the current failure in Psalm
         * From:
         *  - 3.4.0@99e6cd819f829babf50c5852d6f62d40b1fec9d9 (2019-06-03)
         * To at least:
         *  -  Psalm 6.12.0@cf420941d061a57050b6c468ef2c778faf40aee2
         * There is sadly no ->notExpectException()
         */
        // $this->expectException(InvalidArgumentException::class);
        // $this->expectExceptionMessage('Could not get class storage for assertionerror');

        /*
         * Setting allow_string_standin_for_class to false turns the thrown exception into:
         * Psalm\Exception\CodeException: InvalidStringClass - somefile.php:9:31 - String cannot be used as a class
         */
        // Config::getInstance()->allow_string_standin_for_class = false;

        // Current exception:
        //  InvalidArgumentException: Could not get class storage for assertionerror
        Config::getInstance()->allow_string_standin_for_class = true;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace Foo;

                class CrashingClass
                {
                    public function crashingFunction(): void
                    {
                        $variableThatPointsToThrowable = "\\AssertionError";
                        throw new $variableThatPointsToThrowable("failed");
                    }
                }
            ',
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }
}
