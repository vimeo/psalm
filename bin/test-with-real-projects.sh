#!/usr/bin/env bash

set -e
set -x

SCRIPT_DIR="$(dirname "${BASH_SOURCE[0]}")"
SCRIPT_DIR="$(realpath "$SCRIPT_DIR")"
PSALM="$(readlink -f "$SCRIPT_DIR/../psalm")"
PSALM_PHAR="$(readlink -f "$SCRIPT_DIR/../build/psalm.phar")"

cd /tmp/
rm -Rf testing-with-real-projects
mkdir -p testing-with-real-projects
cd testing-with-real-projects

case $1 in
phpunit)
	git clone --depth=1 git@github.com:psalm/endtoend-test-phpunit
	cd endtoend-test-phpunit
	composer install
	"$PSALM_PHAR" --config=.psalm/config.xml --monochrome --show-info=false
	"$PSALM_PHAR" --config=.psalm/static-analysis.xml --monochrome
	;;

collections)
	git clone --depth=1 git@github.com:psalm/endtoend-test-collections.git
	cd endtoend-test-collections
	composer install
	"$PSALM" --monochrome --show-info=false
	;;

psl)
	# For circleCI
	export PHP_EXTENSION_INTL=1
	export PHP_EXTENSION_BCMATH=1

	git clone git@github.com:psalm/endtoend-test-psl.git
	cd endtoend-test-psl
	git checkout 2.3.x
	composer install
	"$PSALM" --monochrome -c config/psalm.xml
	"$PSALM" --monochrome -c config/psalm.xml tests/static-analysis
	;;

laravel)
	git clone --depth=1 git@github.com:psalm/endtoend-test-laravel.git
	cd endtoend-test-laravel
	composer install
	"$PSALM" --monochrome
	;;

*)
	echo "Usage: test-with-real-projects.sh {phpunit|collections|laravel|psl}"
	exit 1
esac
