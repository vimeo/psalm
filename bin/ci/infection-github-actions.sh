#!/bin/bash

set -eu

if [ -z "${SHARD_COUNT:-}" ]; then echo "Did not find env var SHARD_COUNT."; exit 1; fi
if [ -z "${SHARD_NUMBER:-}" ]; then echo "Did not find env var SHARD_NUMBER."; exit 1; fi

mkdir -p build/infection-coverage build/infection

php -d memory_limit=-1 bin/ci/infection-shard-coverage.php \
    "$SHARD_NUMBER" "$SHARD_COUNT" \
    build/coverage-php build/infection-coverage/coverage-xml build/infection-files.txt

php bin/ci/merge-junit.php build/infection-coverage/junit.xml build/junit/*.junit.xml

# the minimum MSI is checked on the summaries of all the shards; two threads, as more parallel test runs exhaust the
# runner's memory
vendor-bin/infection/vendor/infection/infection/bin/infection \
    --filter="$(cat build/infection-files.txt)" \
    --coverage=build/infection-coverage \
    --skip-initial-tests \
    --only-covering-test-cases \
    --with-uncovered \
    --min-msi=0 \
    --threads=2 \
    --no-progress \
    --logger-summary-json="build/infection/summary-$SHARD_NUMBER.json"
