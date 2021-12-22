<?php

namespace Psalm\Tests\Config\Plugin;

use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use SimpleXMLElement;
use stdClass;

class FileTypeSelfRegisteringPlugin implements PluginEntryPointInterface
{
    public const FLAG_SCANNER_TWICE = 1;
    public const FLAG_ANALYZER_TWICE = 2;

    public const FLAG_SCANNER_INVALID = 4;
    public const FLAG_ANALYZER_INVALID = 8;

    /**
     * @var array<string, string>
     */
    public static $names = [];

    /**
     * @var int
     */
    public static $flags = 0;

    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        if (self::$flags & self::FLAG_SCANNER_INVALID) {
            /** @psalm-suppress InvalidArgument */
            $registration->addFileTypeScanner(self::$names['extension'], stdClass::class);
        } else {
            // that's the regular/valid case
            /** @psalm-suppress ArgumentTypeCoercion */
            $registration->addFileTypeScanner(self::$names['extension'], self::$names['scanner']);
        }
        if (self::$flags & self::FLAG_ANALYZER_INVALID) {
            /** @psalm-suppress InvalidArgument */
            $registration->addFileTypeAnalyzer(self::$names['extension'], stdClass::class);
        } else {
            // that's the regular/valid case
            /** @psalm-suppress ArgumentTypeCoercion */
            $registration->addFileTypeAnalyzer(self::$names['extension'], self::$names['analyzer']);
        }

        if (self::$flags & self::FLAG_SCANNER_TWICE) {
            /** @psalm-suppress ArgumentTypeCoercion */
            $registration->addFileTypeScanner(self::$names['extension'], self::$names['scanner']);
        }
        if (self::$flags & self::FLAG_ANALYZER_TWICE) {
            /** @psalm-suppress ArgumentTypeCoercion */
            $registration->addFileTypeAnalyzer(self::$names['extension'], self::$names['analyzer']);
        }
    }
}
