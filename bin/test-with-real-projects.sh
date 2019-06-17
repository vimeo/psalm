#!/usr/bin/env bash

set -e
set -x

cd /tmp/
mkdir testing-with-real-projects
cd testing-with-real-projects
git clone git@github.com:sebastianbergmann/phpunit.git

cd phpunit
git checkout 24b6cfcec34c1167 # release 8.2.2
composer install
~/project/build/psalm.phar --config=.psalm/config.xml --monochrome
