#!/usr/bin/env bash
if ! php -r 'extension_loaded("bz2") or exit(1);' ; then
	echo "You need to install (or enable) bz2 php extension"
	exit 1
fi

composer global require 'humbug/php-scoper:^1.0@dev'
composer global require humbug/box:dev-master
composer install --no-dev
[ -d build ] || mkdir build
# increase FD limit, or Phar compression will fail
ulimit -Sn 4096
php -dphar.readonly=0 `which box` compile

# reinstall deps (to regenerate autoloader and bring back dev deps)
rm -Rf vendor/*
composer install
