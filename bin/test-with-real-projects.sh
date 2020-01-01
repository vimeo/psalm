#!/usr/bin/env bash

set -e
set -x

cd /tmp/
mkdir testing-with-real-projects
cd testing-with-real-projects

git clone git@github.com:sebastianbergmann/phpunit.git
cd phpunit
git checkout 1c2bc44 # bugfix
composer install
~/project/build/psalm.phar --config=.psalm/config.xml --monochrome --show-info=false
~/project/build/psalm.phar --config=.psalm/static-analysis.xml --monochrome

cd /tmp/testing-with-real-projects

git clone git@github.com:muglug/collections.git
cd collections
composer install
~/project/psalm --monochrome --show-info=false

cd /tmp/testing-with-real-projects

git clone git@github.com:Ocramius/ProxyManager.git
cd ProxyManager
composer install
~/project/build/psalm.phar --monochrome

cd /tmp/testing-with-real-projects

git clone git@github.com:roave/you-are-using-it-wrong.git
cd you-are-using-it-wrong
composer install
~/project/build/psalm.phar --monochrome
./vendor/bin/phpunit
