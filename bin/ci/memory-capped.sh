#!/bin/bash

# Runs a command in a memory cgroup of at most MEMORY_MAX (default 12G), as the current user, with the current
# environment: a mutant may start processes the tests' memory limit doesn't cover, and the cgroup's OOM killer then ends
# the largest one, a mutant's, instead of the runner, while the rest of the command goes on.

set -eu

environment=()
while IFS= read -r -d '' variable; do
    environment+=("$variable")
done < <(env -0)

exec sudo env "${environment[@]}" systemd-run --scope --quiet \
    -p "MemoryMax=${MEMORY_MAX:-12G}" -p MemorySwapMax=0 -p OOMPolicy=continue \
    --uid="$(id -u)" --gid="$(id -g)" \
    -- "$@"
