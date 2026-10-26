#!/bin/bash

set -eu

if [ -z "${BASE_REF:-}" ]; then echo "Did not find env var BASE_REF."; exit 1; fi

base="origin/$BASE_REF"

if [ -z "$(git diff --name-only --diff-filter=AM "$base" -- src)" ]; then
    echo "No source changes to mutate."
    exit 0
fi

# Infection runs the tests in a single PHPUnit process, which the whole suite doesn't
# support, so the changed code must be covered by the tests the PR adds or changes.
filter=""
while IFS= read -r file; do
    class="$(basename "$file" .php)"
    filter="${filter:+$filter|}$class"
    # TaintTest's providers run through tests/Taint/TaintCases*Test.php.
    if [ "$class" = TaintTest ]; then
        filter="$filter|TaintCases"
    fi
done < <(git diff --name-only --diff-filter=AM "$base" -- tests | grep 'Test\.php$' || true)

if [ -z "$filter" ]; then
    echo "The PR changes src/ but no test file: add tests that cover the change."
    exit 1
fi

echo "Running Infection with the tests matching: $filter"

composer mutation -- \
    --git-diff-base="$base" \
    --no-progress \
    --show-mutations \
    --test-framework-options="--filter=($filter)"
