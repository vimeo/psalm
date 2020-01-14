#!/usr/bin/env bash

set -e
set -x

cd /tmp/
mkdir testing-with-real-projects
cd testing-with-real-projects

git clone git@github.com:muglug/phpunit.git
cd phpunit
composer install
~/project/build/psalm.phar --config=.psalm/config.xml --monochrome --show-info=false
~/project/build/psalm.phar --config=.psalm/static-analysis.xml --monochrome

cd /tmp/testing-with-real-projects

git clone git@github.com:muglug/collections.git
cd collections
composer install
~/project/psalm --monochrome --show-info=false

cd /tmp/testing-with-real-projects

git clone git@github.com:muglug/ProxyManager.git
cd ProxyManager
composer install
~/project/psalm --monochrome

cd /tmp/testing-with-real-projects

git clone git@github.com:muglug/you-are-using-it-wrong.git
cd you-are-using-it-wrong
git checkout 5d29951fd0c49570e566c3dd5118eb18dc9c67bb
composer install
~/project/psalm --monochrome
