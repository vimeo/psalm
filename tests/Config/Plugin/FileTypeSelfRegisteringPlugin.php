<?php

declare(strict_types=1);

namespace Psalm\Tests\Config\Plugin;

use Psalm\Plugin\FileExtensionsInterface;
use Psalm\Plugin\PluginFileExtensionsInterface;
use SimpleXMLElement;
use stdClass;

class FileTypeSelfRegisteringPlugin implements PluginFileExtensionsInterface
{
    public const FLAG_SCANNER_TWICE = 1;
    public const FLAG_ANALYZER_TWICE = 2;

    public const FLAG_SCANNER_INVALID = 4;
    public const FLAG_ANALYZER_INVALID = 8;

    /**
     * @var array<string, string>
     */
    public static array $names = [];

    public static int $flags = 0;

    public function processFileExtensions(FileExtensionsInterface $fileExtensions, ?SimpleXMLElement $config = null): void
    {
        if (self::$flags & self::FLAG_SCANNER_INVALID) {
            /** @psalm-suppress InvalidArgument */
            $fileExtensions->addFileTypeScanner(self::$names['extension'], stdClass::class);
        } else {
            // that's the regular/valid case
            /** @psalm-suppress ArgumentTypeCoercion */
            $fileExtensions->addFileTypeScanner(self::$names['extension'], self::$names['scanner']);
        }
        if (self::$flags & self::FLAG_ANALYZER_INVALID) {
            /** @psalm-suppress InvalidArgument */
            $fileExtensions->addFileTypeAnalyzer(self::$names['extension'], stdClass::class);
        } else {
            // that's the regular/valid case
            /** @psalm-suppress ArgumentTypeCoercion */
            $fileExtensions->addFileTypeAnalyzer(self::$names['extension'], self::$names['analyzer']);
        }

        if (self::$flags & self::FLAG_SCANNER_TWICE) {
            /** @psalm-suppress ArgumentTypeCoercion */
            $fileExtensions->addFileTypeScanner(self::$names['extension'], self::$names['scanner']);
        }
        if (self::$flags & self::FLAG_ANALYZER_TWICE) {
            /** @psalm-suppress ArgumentTypeCoercion */
            $fileExtensions->addFileTypeAnalyzer(self::$names['extension'], self::$names['analyzer']);
        }
    }
}
