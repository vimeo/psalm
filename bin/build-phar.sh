#!/usr/bin/env bash
composer global require humbug/box:dev-master
composer install --no-dev
[ -d build ] || mkdir build
# increase FD limit, or Phar compression will fail
ulimit -Sn 4096
box build

# reinstall deps (to regenerate autoloader and bring back dev deps)
rm -Rf vendor/*
composer install
