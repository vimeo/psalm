#!/usr/bin/env bash

set -e
set -x

cd /tmp/
mkdir -p testing-with-real-projects
cd testing-with-real-projects

case $1 in
phpunit)
	git clone --depth=1 git@github.com:psalm/endtoend-test-phpunit
	cd endtoend-test-phpunit
	composer install
	~/project/build/psalm.phar --config=.psalm/config.xml --monochrome --show-info=false
	~/project/build/psalm.phar --config=.psalm/static-analysis.xml --monochrome
	;;

collections)
	git clone --depth=1 git@github.com:muglug/collections.git
	cd collections
	composer install
	~/project/psalm --monochrome --show-info=false
	;;

psl)
	git clone git@github.com:psalm/endtoend-test-psl.git
	cd endtoend-test-psl
	git checkout 1.6.x
	composer install --ignore-platform-reqs
	~/project/psalm --monochrome --config=tools/psalm/psalm.xml
	;;

laravel)
	git clone --depth=1 git@github.com:muglug/framework.git
	cd framework
	composer install
	~/project/psalm --monochrome
	;;
*)
	echo "Usage: test-with-real-projects.sh {phpunit|collections|proxymanager|laravel|psl}"
	exit 1
esac
