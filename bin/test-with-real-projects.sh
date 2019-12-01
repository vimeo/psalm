#!/usr/bin/env bash

set -e
set -x

cd /tmp/
mkdir testing-with-real-projects
cd testing-with-real-projects
git clone git@github.com:sebastianbergmann/phpunit.git

cd phpunit
git checkout 4db048f # small template fix
composer install
~/project/build/psalm.phar --config=.psalm/config.xml --monochrome --show-info=false
~/project/build/psalm.phar --config=.psalm/static-analysis.xml --monochrome
