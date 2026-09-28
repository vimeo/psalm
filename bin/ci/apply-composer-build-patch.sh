#!/bin/bash
set -euo pipefail

# Downloads the composer-build patch produced by the "Build docker image for
# stubs" workflow for the current commit on the current branch, and applies it.
#
# The build-stubs workflow runs `composer build` against the freshly-built stub
# images and, when that changes any committed file, uploads the diff as the
# `composer-build-patch` artifact. This script fetches that artifact for HEAD and
# applies it, so regenerated callmaps (etc.) can be pulled in without rerunning
# the images locally.
#
# Requires the GitHub CLI (`gh`), authenticated with access to the repo.

REPO="${PSALM_REPO:-vimeo/psalm}"
WORKFLOW="build-stubs.yml"
ARTIFACT="composer-build-patch"
PATCH="composer-build.patch"

if ! command -v gh >/dev/null 2>&1; then
    echo "This script needs the GitHub CLI (gh): https://cli.github.com/" >&2
    exit 1
fi

commit="$(git rev-parse HEAD)"
branch="$(git rev-parse --abbrev-ref HEAD)"

echo "Looking for the latest '$ARTIFACT' for commit $commit on branch $branch ($REPO)..."

run_id="$(gh run list --repo "$REPO" --workflow "$WORKFLOW" --branch "$branch" \
    --limit 20 --json databaseId,headSha \
    --jq "map(select(.headSha == \"$commit\")) | .[0].databaseId")"

if [ -z "$run_id" ] || [ "$run_id" = "null" ]; then
    echo "No $WORKFLOW run found for commit $commit on branch $branch." >&2
    echo "Has the commit been pushed and the workflow run yet?" >&2
    exit 1
fi

echo "Found run $run_id."

tmp="$(mktemp -d)"
trap 'rm -rf "$tmp"' EXIT

if ! gh run download "$run_id" --repo "$REPO" --name "$ARTIFACT" --dir "$tmp" 2>/dev/null; then
    echo "Run $run_id has no '$ARTIFACT' artifact - composer build produced no diff"
    echo "(nothing to apply) or the run has not finished yet."
    exit 0
fi

patch="$(find "$tmp" -type f -name "$PATCH" | head -n1)"

if [ -z "$patch" ] || [ ! -s "$patch" ]; then
    echo "The '$ARTIFACT' artifact contained no patch. Nothing to apply."
    exit 0
fi

echo "Applying $PATCH..."
git apply "$patch"
echo "Applied. Review the changes with 'git diff'."
