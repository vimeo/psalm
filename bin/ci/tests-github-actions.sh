#!/bin/bash

set -eu

if [ -z "${CHUNK_COUNT:-}" ]; then echo "Did not find env var CHUNK_COUNT."; exit 1; fi
if [ -z "${CHUNK_NUMBER:-}" ]; then echo "Did not find env var CHUNK_NUMBER."; exit 1; fi

# One testsuite per runner, balanced by how long each test file takes (see generate_testsuites.php).
php bin/ci/generate_testsuites.php "$CHUNK_COUNT"

mkdir -p build/phpunit

# Runs the chunk's test files in parallel on every core.
vendor/bin/paratest \
    --processes="$(nproc)" \
    --testsuite="chunk_$CHUNK_NUMBER" \
    --log-junit=build/phpunit/junit.xml \
    --colors=always
