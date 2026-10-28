#!/bin/bash

# Runs a command in a memory cgroup of at most MEMORY_MAX (default 12G), as the current user: a mutant may start
# processes the tests' memory limit doesn't cover, and the cgroup's OOM killer then ends them instead of the runner.

set -eu

exec sudo --preserve-env env "PATH=$PATH" "HOME=$HOME" systemd-run --scope --quiet \
    -p "MemoryMax=${MEMORY_MAX:-12G}" -p MemorySwapMax=0 \
    --uid="$(id -u)" --gid="$(id -g)" \
    -- "$@"
