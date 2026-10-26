#!/bin/bash

set -eu

if [ -z "${CHUNK_COUNT:-}" ]; then echo "Did not find env var CHUNK_COUNT."; exit 1; fi
if [ -z "${CHUNK_NUMBER:-}" ]; then echo "Did not find env var CHUNK_NUMBER."; exit 1; fi

php bin/ci/generate_testsuites.php "$CHUNK_COUNT"

mkdir -p build/coverage-php build/junit

vendor/bin/paratest \
    --max-batch-size=1 \
    --processes="$(nproc)" \
    --testsuite="chunk_$CHUNK_NUMBER" \
    --cache-directory=build/phpunit-cache \
    --coverage-php="build/coverage-php/chunk_$CHUNK_NUMBER.cov" \
    --log-junit="build/junit/chunk_$CHUNK_NUMBER.junit.xml"
