#!/usr/bin/env bash
./vendor/bin/php-cs-fixer fix --verbose --config ./.php_cs.dist -- $(git diff --name-only $TRAVIS_COMMIT_RANGE | grep '\.php$') .php_cs.dist;
