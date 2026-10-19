#!/bin/bash

set -eu

if [ -z "${CHUNK_COUNT:-}" ]; then echo "Did not find env var CHUNK_COUNT."; exit 1; fi
if [ -z "${CHUNK_NUMBER:-}" ]; then echo "Did not find env var CHUNK_NUMBER."; exit 1; fi

# One testsuite per runner, balanced by how long each test file takes (see generate_testsuites.php).
php bin/ci/generate_testsuites.php "$CHUNK_COUNT"

mkdir -p build/phpunit

# Functional mode runs batches of a file's tests in separate processes, so a long file uses every core.
vendor/bin/paratest \
    --functional \
    --max-batch-size=100 \
    --processes="$(nproc)" \
    --testsuite="chunk_$CHUNK_NUMBER" \
    --log-junit=build/phpunit/junit.xml \
    --colors=always
