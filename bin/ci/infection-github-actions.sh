#!/bin/bash

set -eu

if [ -z "${BASE_REF:-}" ]; then echo "Did not find env var BASE_REF."; exit 1; fi

mkdir -p build/infection-coverage

vendor-bin/infection/vendor/phpunit/phpcov/phpcov merge \
    --xml build/infection-coverage/coverage-xml \
    build/coverage-php

php bin/ci/merge-junit.php build/infection-coverage/junit.xml build/junit/*.junit.xml

composer mutation -- \
    --git-diff-base="origin/$BASE_REF" \
    --coverage=build/infection-coverage \
    --skip-initial-tests \
    --only-covering-test-cases \
    --no-progress \
    --show-mutations=max
