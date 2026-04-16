#!/usr/bin/env bash

set -e
set -x

SCRIPT_DIR="$(dirname "${BASH_SOURCE[0]}")"
SCRIPT_DIR="$(realpath "$SCRIPT_DIR")"
PSALM="$(readlink -f "$SCRIPT_DIR/../../psalm")"
PSALM_PHAR="$(readlink -f "$SCRIPT_DIR/../../build/psalm.phar" || echo "")"

if [ ! -f "$PSALM_PHAR" ]; then
	PSALM_PHAR="$PSALM"
	cd "$SCRIPT_DIR/../../"
	composer update --no-dev --prefer-dist
fi

which gsed > /dev/null && sed=gsed || sed=sed

rm -Rf /tmp/testing-with-real-projects
mkdir -p /tmp/testing-with-real-projects
cd /tmp/testing-with-real-projects

FAIL=0

case $1 in
update)
	cd "$OLDPWD"
	"${BASH_SOURCE[0]}" phpunit update || true
	"${BASH_SOURCE[0]}" collections update || true
	"${BASH_SOURCE[0]}" laravel update || true
	exit 0
	;;
phpunit)
	git clone --depth=1 git@github.com:psalm/endtoend-test-phpunit
	cd endtoend-test-phpunit
	composer install
	"$PSALM_PHAR" --config=.psalm/config.xml --monochrome --show-info=false --set-baseline=.psalm/baseline.xml || FAIL=$?
	"$PSALM_PHAR" --config=.psalm/static-analysis.xml --monochrome --set-baseline=.psalm/static-analysis-baseline.xml || FAIL=$?
	;;

collections)
	git clone --depth=1 git@github.com:psalm/endtoend-test-collections.git
	cd endtoend-test-collections
	composer install
	rm vendor/amphp/amp/lib/functions.php; touch vendor/amphp/amp/lib/functions.php;
	rm vendor/amphp/amp/lib/Internal/functions.php; touch vendor/amphp/amp/lib/Internal/functions.php
	rm vendor/amphp/byte-stream/lib/functions.php; touch vendor/amphp/byte-stream/lib/functions.php
	"$PSALM" --monochrome --show-info=false --set-baseline=psalm-baseline.xml || FAIL=$?
	;;

laravel)
	git clone --depth=1 git@github.com:psalm/endtoend-test-laravel.git
	cd endtoend-test-laravel
	composer install --ignore-platform-reqs
	"$PSALM" --monochrome --set-baseline=psalm-baseline.xml || FAIL=$?
	;;

*)
	echo "Usage: test-with-real-projects.sh {phpunit|collections|laravel}"
	echo "Usage: test-with-real-projects.sh update"
	exit 1
esac

if [ "$2" == "update" ]; then
	git commit -am 'Update baseline'
	git push
fi

exit $FAIL