#!/usr/bin/env bash

set -e

php -v

composer bin box install

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

php $DIR/improve_class_alias.php

php -r 'require "vendor/autoload.php"; Psalm\Internal\VersionUtils::dump();'
if [[ ! -f build/phar-versions.php ]] ; then
   echo "failed to dump versions";
   exit;
fi

vendor/bin/box compile --no-parallel

# The phar's autoloader runs before the project's: a global alias it declares for a function the phar never defines
# shadows the project's or an extension's function of that name (e.g. lz4_compress()) with one that always fails.
# Polyfills may only declare their function when an extension is loaded, so look for declarations in the autoloaded
# files instead of calling the aliases.
PHAR="$PWD/build/psalm.phar" php <<'PHP'
<?php
$phar = 'phar://' . realpath(getenv('PHAR'));
preg_match_all(
    '/function_exists\(\'\w+\'\)\) \{ function \w+\(\) \{ return \\\\PsalmPhar\\\\(\w+)\(/',
    file_get_contents($phar . '/vendor/autoload.php'),
    $aliases,
);
if (!$aliases[1]) {
    fwrite(STDERR, 'No function aliases found in the phar autoloader: did the format box generates change?' . PHP_EOL);
    exit(1);
}
$declared = [];
$seen = [];
$files = array_values(require $phar . '/vendor/composer/autoload_files.php');
while (null !== $file = array_pop($files)) {
    if (isset($seen[$file])) {
        continue;
    }
    $seen[$file] = true;
    $code = file_get_contents($file);
    // polyfill bootstraps require the file that declares the functions
    preg_match_all('/(?:require|include)(?:_once)?\s*\(?\s*__DIR__\s*\.\s*\'([^\']+)\'/', $code, $required);
    array_push($files, ...array_map(static fn(string $path): string => dirname($file) . $path, $required[1]));
    if (preg_match('/^namespace PsalmPhar;/m', $code) && preg_match_all('/^\s*function (\w+)\s*\(/m', $code, $functions)) {
        $declared += array_fill_keys(array_map(strtolower(...), $functions[1]), true);
    }
}
$dangling = array_filter($aliases[1], static fn(string $f): bool => !isset($declared[strtolower($f)]));
if ($dangling) {
    fwrite(STDERR, 'The phar declares global aliases to functions it never defines: ' . implode(', ', $dangling) . PHP_EOL);
    exit(1);
}
PHP

# scoper.inc.php unprefixes every string literal in Psalm's code as a name of analysed code. One naming a library the
# phar ships would then load the project's copy instead of the phar's, so none may.
PHAR="$PWD/build/psalm.phar" php <<'PHP'
<?php
$phar = 'phar://' . realpath(getenv('PHAR'));
$composer = $phar . '/vendor/composer';
$shipped = [];
$namespaces = [...array_keys(require $composer . '/autoload_psr4.php'), ...array_keys(require $composer . '/autoload_namespaces.php')];
// global classes the phar ships are polyfills of PHP's own (ValueError, Deprecated, ...), which Psalm's strings name
$classes = array_filter(array_keys(require $composer . '/autoload_classmap.php'), static fn(string $class): bool => substr_count($class, '\\') > 1);
foreach ([...$namespaces, ...$classes] as $name) {
    if (str_starts_with($name, 'PsalmPhar\\')) {
        $shipped[] = strtolower(rtrim(substr($name, strlen('PsalmPhar\\')), '\\'));
    }
}
$found = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($phar . '/src/Psalm', FilesystemIterator::SKIP_DOTS)) as $file) {
    foreach (token_get_all(file_get_contents($file->getPathname())) as $token) {
        if (!is_array($token) || ($token[0] !== T_CONSTANT_ENCAPSED_STRING && $token[0] !== T_ENCAPSED_AND_WHITESPACE)) {
            continue;
        }
        $string = strtolower(ltrim(str_replace('\\\\', '\\', trim($token[1], '\'"')), '\\'));
        foreach ($shipped as $name) {
            if ($string === $name || str_starts_with($string, $name . '\\')) {
                $found[] = substr($file->getPathname(), strlen($phar) + 1) . ': ' . $token[1];
                break;
            }
        }
    }
}
if ($found) {
    fwrite(STDERR, "Strings in Psalm's code name libraries the phar ships:\n" . implode(PHP_EOL, $found) . PHP_EOL);
    exit(1);
}
PHP

# The bundled plugins name both the phar's own libraries and the analysed project's. A name left prefixed that isn't
# one of the libraries they use as tools is an analysed name the plugin would never match.
PHAR="$PWD/build/psalm.phar" php <<'PHP'
<?php
$phar = 'phar://' . realpath(getenv('PHAR'));
$tools = ['phpparser', 'composer\installedversions', 'composer\semver', 'sebastianbergmann\diff', 'webmozart\assert'];
$found = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($phar . '/vendor/psalm', FilesystemIterator::SKIP_DOTS)) as $file) {
    $path = substr($file->getPathname(), strlen($phar) + 1);
    if (!str_starts_with($path, 'vendor/psalm/plugin-') || $file->getExtension() !== 'php') {
        continue;
    }
    preg_match_all('/PsalmPhar\\\\{1,2}([A-Za-z_][A-Za-z0-9_\\\\]*)/', file_get_contents($file->getPathname()), $names);
    foreach ($names[1] as $name) {
        $name = strtolower(rtrim(str_replace('\\\\', '\\', $name), '\\'));
        foreach ($tools as $tool) {
            if ($name === $tool || str_starts_with($name, $tool . '\\')) {
                continue 2;
            }
        }
        $found[] = $path . ': ' . $name;
    }
}
if ($found) {
    fwrite(STDERR, "The bundled plugins name prefixed classes that aren't the phar's tools:\n" . implode(PHP_EOL, array_unique($found)) . PHP_EOL);
    exit(1);
}
PHP

rm -rf /tmp/psalmTest
mkdir -p /tmp/psalmTest
cd /tmp/psalmTest
composer require danog/loop # Just to gen the composer.json

$OLDPWD/build/psalm.phar -v

# Psalm's own code names analysed classes: an ext-decimal Decimal may be cast to int
mkdir -p decimal/src
cat > decimal/src/Decimal.php <<'EOF'
<?php

namespace Decimal;

final class Decimal
{
}
EOF
cat > decimal/src/Money.php <<'EOF'
<?php

namespace App;

final class Money
{
    public static function cents(\Decimal\Decimal $amount): int
    {
        return (int) $amount;
    }
}
EOF
cat > decimal/psalm.xml <<'EOF'
<?xml version="1.0"?>
<psalm errorLevel="1" findUnusedCode="false">
    <projectFiles>
        <directory name="src"/>
    </projectFiles>
</psalm>
EOF
$OLDPWD/build/psalm.phar --root=decimal --no-cache --no-progress

# Bundled plugins must still match the analysed libraries: with plugin-phpunit working, FooTest is not reported as unused
php -r '$c = json_decode(file_get_contents("composer.json"), true); $c["autoload-dev"]["psr-4"]["Tests\\"] = "tests/"; file_put_contents("composer.json", json_encode($c));'
composer require --dev phpunit/phpunit:^11
mkdir tests
cat > tests/FooTest.php <<'EOF'
<?php

namespace Tests;

final class FooTest extends \PHPUnit\Framework\TestCase
{
    public function testFoo(): void
    {
        $this->assertTrue(true);
    }
}
EOF
cat > psalm.xml <<'EOF'
<?xml version="1.0"?>
<psalm errorLevel="1" findUnusedCode="true">
    <projectFiles>
        <directory name="tests"/>
    </projectFiles>
</psalm>
EOF
$OLDPWD/build/psalm.phar --no-cache --no-progress

# plugin-laravel boots the project's application and works on its objects (Illuminate and Symfony classes, helpers)
composer create-project --no-scripts --no-interaction laravel/laravel:^12 laravel
mkdir -p laravel/app/Console/Commands
cat > laravel/app/Console/Commands/GreetCommand.php <<'EOF'
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

final class GreetCommand extends Command
{
    protected $signature = 'app:greet {name}';

    public function handle(): void
    {
        $this->info((string) $this->argument('nmae'));
    }
}
EOF
cat > laravel/psalm.xml <<'EOF'
<?xml version="1.0"?>
<psalm errorLevel="8">
    <projectFiles>
        <directory name="app"/>
    </projectFiles>
    <plugins>
        <pluginClass class="Psalm\LaravelPlugin\Plugin">
            <failOnInternalError value="true"/>
        </pluginClass>
    </plugins>
</psalm>
EOF
# exit code 2 only means issues were found (a crash exits with 1); the plugin must report the misspelled argument
output=$($OLDPWD/build/psalm.phar --root=laravel --no-cache --no-progress --output-format=compact) || [ $? -eq 2 ]
echo "$output"
grep -q InvalidConsoleArgumentName <<< "$output"

# plugin-symfony matches the project's Symfony classes: it must report the mode that isn't an InputArgument constant
composer create-project --no-scripts --no-interaction symfony/skeleton:^7 symfony
mkdir -p symfony/src/Command
cat > symfony/src/Command/GreetCommand.php <<'EOF'
<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

final class GreetCommand extends Command
{
    protected function configure(): void
    {
        $this->setDefinition([new InputArgument('name', 4)]);
    }
}
EOF
cat > symfony/psalm.xml <<'EOF'
<?xml version="1.0"?>
<psalm errorLevel="8">
    <projectFiles>
        <directory name="src"/>
    </projectFiles>
</psalm>
EOF
output=$($OLDPWD/build/psalm.phar --root=symfony --no-cache --no-progress --output-format=compact) || [ $? -eq 2 ]
echo "$output"
grep -q InvalidConsoleArgumentValue <<< "$output"

cd $OLDPWD
rm -rf /tmp/psalmTest

if [[ "$GPG_SIGNING" != '' ]] ; then
    if [[ "$GPG_SECRET_KEY" != '' ]] ; then
        echo "Load secret key into gpg"
        echo "$GPG_SECRET_KEY" | gpg --import --no-tty --batch --yes
    fi

    echo "Sign Phar"

    echo "$GPG_PASSPHRASE" | gpg --command-fd 0 --passphrase-fd 0 --pinentry-mode loopback -u 99BF4D9A33D65E1E --batch --detach-sign --armor --output build/psalm.phar.asc build/psalm.phar
fi
