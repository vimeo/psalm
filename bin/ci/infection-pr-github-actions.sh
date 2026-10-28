#!/bin/bash

set -eu

if [ -z "${BASE_REF:-}" ]; then echo "Did not find env var BASE_REF."; exit 1; fi

mkdir -p build/infection-coverage build/infection

# Infection only mutates the lines the pull request changes, so the coverage of the files it changes is enough
git diff --name-only --diff-filter=AMR "origin/$BASE_REF..." -- src > build/infection-changed-files.txt

php -d memory_limit=-1 bin/ci/infection-shard-coverage.php \
    @build/infection-changed-files.txt \
    build/coverage-php build/infection-coverage/coverage-xml build/infection-files.txt

php bin/ci/merge-junit.php build/infection-coverage/junit.xml build/junit/*.junit.xml

# a mutant may allocate without end: without a memory limit for its tests, it exhausts the runner's memory
export PSALM_TEST_MEMORY_LIMIT=4G

# every mutant of the changed lines must be killed, uncovered ones included
vendor-bin/infection/vendor/infection/infection/bin/infection \
    --git-diff-lines \
    --git-diff-base="origin/$BASE_REF" \
    --coverage=build/infection-coverage \
    --skip-initial-tests \
    --only-covering-test-cases \
    --with-uncovered \
    --min-msi=100 \
    --ignore-msi-with-no-mutations \
    --threads=2 \
    --no-progress \
    --logger-github
