<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Config;
use Psalm\Context;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function getcwd;

use const DIRECTORY_SEPARATOR;

final class MissingExtensionRequirementTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    #[Override]
    protected function makeConfig(): Config
    {
        $config = parent::makeConfig();
        $config->declared_extensions = ['pdo' => true, 'sodium' => true];

        return $config;
    }

    public function testNothingIsReportedWithoutAComposerJson(): void
    {
        $this->project_analyzer->getConfig()->declared_extensions = null;

        $file_path = (string) getcwd() . '/src/somefile.php';
        $this->addFile($file_path, '<?php
            function length(string $s): int {
                return mb_strlen($s);
            }');

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'coreExtensions' => [
                'code' => '<?php
                    function f(string $s): string {
                        $d = new DateTime();
                        $o = new ArrayObject([]);
                        return strlen($s) . preg_quote($s) . hash("sha256", $s) . json_encode($s)
                            . $d->format("Y") . count($o) . random_int(1, 2);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'declaredExtensions' => [
                'code' => '<?php
                    function f(string $s): string {
                        $pdo = new PDO("sqlite::memory:");
                        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        return sodium_bin2hex($s);
                    }',
            ],
            'referencesThatDontNeedTheClass' => [
                'code' => '<?php
                    /** @param SoapClient $docblock */
                    function f(Redis $r, mixed $x, $docblock): ?Redis {
                        $name = Redis::class;
                        return $x instanceof Redis ? $x : null;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'extensionLoaded' => [
                'code' => '<?php
                    function f(string $s): int {
                        if (extension_loaded("mbstring")) {
                            return mb_strlen($s);
                        }
                        return strlen($s);
                    }',
            ],
            'extensionLoadedEarlyReturn' => [
                'code' => '<?php
                    function f(string $s): int {
                        if (!extension_loaded("mbstring")) {
                            return strlen($s);
                        }
                        return mb_strlen($s);
                    }',
            ],
            'extensionLoadedInTheCondition' => [
                'code' => '<?php
                    function f(string $s): bool {
                        return extension_loaded("ctype") && ctype_digit($s);
                    }',
            ],
            'functionOfTheExtensionExists' => [
                'code' => '<?php
                    function f(string $s): string {
                        if (function_exists("iconv")) {
                            return (string) iconv("UTF-8", "ASCII", $s) . iconv_strlen($s);
                        }
                        return $s;
                    }',
            ],
            'classOfTheExtensionExists' => [
                'code' => '<?php
                    function f(): ?ZipArchive {
                        return class_exists(ZipArchive::class) ? new ZipArchive() : null;
                    }',
            ],
            'closureOfAGuardedBranch' => [
                'code' => '<?php
                    function f(): ?Closure {
                        if (extension_loaded("posix")) {
                            return fn(): int => posix_getpid();
                        }
                        return null;
                    }',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
            'functionCall' => [
                'code' => '<?php
                    function f(string $s): int {
                        return mb_strlen($s);
                    }',
                'error_message' => 'MissingExtensionRequirement - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:32'
                    . ' - mb_strlen is part of the ext-mbstring extension',
            ],
            'new' => [
                'code' => '<?php
                    function f(): ZipArchive {
                        return new ZipArchive();
                    }',
                'error_message' => 'MissingExtensionRequirement - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:36',
            ],
            'staticCall' => [
                'code' => '<?php
                    function f(): ?int {
                        return IntlChar::ord("a");
                    }',
                'error_message' => 'IntlChar is part of the ext-intl extension',
            ],
            'classConstant' => [
                'code' => '<?php
                    function f(): int {
                        return ZipArchive::CREATE;
                    }',
                'error_message' => 'ZipArchive is part of the ext-zip extension',
            ],
            'extends' => [
                'code' => '<?php
                    final class Client extends SoapClient {}',
                'error_message' => 'SoapClient is part of the ext-soap extension',
            ],
            'implementsAnInterfaceOfJsonBeforePhp8' => [
                'code' => '<?php
                    final class A implements JsonSerializable {
                        public function jsonSerialize(): array {
                            return [];
                        }
                    }',
                'error_message' => 'JsonSerializable is part of the ext-json extension',
            ],
            'guardOfAnotherExtension' => [
                'code' => '<?php
                    function f(string $s): int {
                        if (extension_loaded("intl")) {
                            return mb_strlen($s);
                        }
                        return 0;
                    }',
                'error_message' => 'mb_strlen is part of the ext-mbstring extension',
            ],
            'branchWhereTheExtensionIsMissing' => [
                'code' => '<?php
                    function f(string $s): int {
                        if (!extension_loaded("mbstring")) {
                            return mb_strlen($s);
                        }
                        return 0;
                    }',
                'error_message' => 'mb_strlen is part of the ext-mbstring extension',
            ],
            'afterTheGuardedBranch' => [
                'code' => '<?php
                    function f(string $s): int {
                        if (extension_loaded("mbstring")) {
                            echo mb_strlen($s);
                        }
                        return mb_strlen($s);
                    }',
                'error_message' => 'MissingExtensionRequirement - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:32',
            ],
            'composerNameOfOpcache' => [
                'code' => '<?php
                    function f(): bool {
                        return opcache_get_status() !== false;
                    }',
                'error_message' => 'opcache_get_status is part of the ext-zend-opcache extension',
            ],
            'polyfillDeclaredInTheCode' => [
                'code' => '<?php
                    if (!function_exists("bcadd")) {
                        function bcadd(string $a, string $b): string {
                            return (string) ((int) $a + (int) $b);
                        }
                    }
                    echo bcadd("1", "2");',
                'error_message' => 'bcadd is part of the ext-bcmath extension',
            ],
        ];
    }
}
