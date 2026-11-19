<?php

declare(strict_types=1);

namespace Psalm\Tests\Taint;

use Override;

/**
 * The properties a constructor of the stub of stubs/libraries for monolog/monolog initializes, which its empty body
 * doesn't show
 */
final class MonologLibraryStubPropertiesTest extends LibraryStubsTestCase
{
    /**
     * @psalm-pure
     */
    #[Override]
    protected static function getLibraries(): string
    {
        return '<?php
            namespace Monolog\Formatter {
                interface FormatterInterface {}
                class NormalizerFormatter implements FormatterInterface {
                    protected string $dateFormat;
                    public function __construct(?string $dateFormat = null) {
                        $this->dateFormat = $dateFormat ?? "Y-m-d";
                    }
                }
                class JsonFormatter extends NormalizerFormatter {
                    public const BATCH_MODE_JSON = 1;
                    protected int $batchMode;
                    public function __construct(int $batchMode = self::BATCH_MODE_JSON) {
                        $this->batchMode = $batchMode;
                        parent::__construct();
                    }
                }
            }

            namespace Vendor {
                class BaseFormatter extends \Monolog\Formatter\JsonFormatter {
                    protected int $depth;
                    public function __construct() {
                        parent::__construct();
                        $this->depth = 1;
                    }
                }
            }';
    }

    /**
     * @return non-empty-list<string>
     * @psalm-pure
     */
    #[Override]
    protected static function getReportedIssueTypePrefixes(): array
    {
        return ['PropertyNotSetInConstructor'];
    }

    /**
     * @return array<string, array{string, list<string>}>
     * @psalm-pure
     */
    #[Override]
    public static function providerTaints(): array
    {
        return [
            'parentConstructorOfAStubbedClassInitializesItsProperties' => [
                '<?php
                    final class MyFormatter extends Monolog\Formatter\JsonFormatter {
                        private string $name;

                        public function __construct(string $name) {
                            parent::__construct();
                            $this->name = $name;
                        }

                        public function getName(): string {
                            return $this->name;
                        }
                    }

                    final class MyBaseFormatter extends Vendor\BaseFormatter {
                        public function __construct() {
                            parent::__construct();
                        }
                    }',
                [],
            ],
            'propertiesOfAStubbedClassAreNotInitializedWithoutItsConstructor' => [
                '<?php
                    final class MyFormatter extends Monolog\Formatter\JsonFormatter {
                        public function __construct() {}
                    }',
                [
                    'PropertyNotSetInConstructor on line 2',
                    'PropertyNotSetInConstructor on line 2',
                ],
            ],
        ];
    }
}
