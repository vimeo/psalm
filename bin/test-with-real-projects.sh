#!/usr/bin/env bash

set -e
set -x

cd /tmp/
mkdir testing-with-real-projects
cd testing-with-real-projects

git clone git@github.com:muglug/phpunit.git
cd phpunit
git checkout 69a81ac # bugfix
composer install
~/project/build/psalm.phar --config=.psalm/config.xml --monochrome --show-info=false
~/project/build/psalm.phar --config=.psalm/static-analysis.xml --monochrome

cd /tmp/testing-with-real-projects

git clone git@github.com:doctrine/collections.git
cd collections
composer install
~/project/build/psalm.phar --monochrome --show-info=false
