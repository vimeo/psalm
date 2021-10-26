#!/usr/bin/env bash

set -e
set -x

SCRIPT_DIR="$(dirname "${BASH_SOURCE[0]}")"
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
	git clone --depth=1 git@github.com:muglug/collections.git
	cd collections
	composer install
	"$PSALM" --monochrome --show-info=false
	;;

psl)
	git clone git@github.com:azjezz/psl.git
	cd endtoend-test-psl
	git checkout 1.8.x
	composer install --ignore-platform-reqs
	composer require --dev php-standard-library/psalm-plugin --ignore-platform-reqs
	"$PSALM" --monochrome --config=tools/psalm/psalm.xml
	;;

laravel)
	git clone --depth=1 git@github.com:muglug/framework.git
	cd framework
	composer install
	"$PSALM" --monochrome
	;;
*)
	echo "Usage: test-with-real-projects.sh {phpunit|collections|proxymanager|laravel|psl}"
	exit 1
esac
