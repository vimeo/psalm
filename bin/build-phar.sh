#!/usr/bin/env bash
if ! php -r 'extension_loaded("bz2") or exit(1);' ; then
	echo "You need to install (or enable) bz2 php extension"
	exit 1
fi

composer global require 'humbug/php-scoper:^1.0@dev'
composer global require humbug/box:dev-master
composer install --no-dev
[ -d build ] || mkdir build
[ -d build/psalm ] || mkdir build/psalm
# increase FD limit, or Phar compression will fail
ulimit -Sn 4096

rm -f bin/psalm.phar

# Prefixes the code to be bundled
php -d memory_limit=-1 `which php-scoper` add-prefix --output-dir=build/psalm --force

[ -d build/psalm/src/Psalm/Stubs ] || mkdir build/psalm/src/Psalm/Stubs

cp src/Psalm/Stubs/CoreGenericFunctions.php build/psalm/src/Psalm/Stubs
cp src/Psalm/Stubs/CoreGenericClasses.php build/psalm/src/Psalm/Stubs

# Re-dump the loader to account for the prefixing
# and optimize the loader
composer dump-autoload --working-dir=build/psalm --classmap-authoritative --no-dev

php -d memory_limit=-1 -d phar.readonly=0 `which box` --debug --dev compile

# clean up build
rm -Rf build/psalm
rm -Rf .box

# reinstall deps (to regenerate autoloader and bring back dev deps)
rm -Rf vendor/*
composer install
