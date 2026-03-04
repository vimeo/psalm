#!/bin/bash -e

ACTOR="${1:-danog}"

for file in bin/stubs/Dockerfile_*; do
    # if none found - use shopt -s nullglob alternatively
    if [[ ! -e "$file" ]]; then
        break
    fi

    f="${file#bin/stubs/Dockerfile_}"
    echo "Starting ${f}"

    pull="pull"
    image="ghcr.io/${ACTOR}/psalm:internal_stubs_${f}"
    if [[ "$ACTOR" == local ]] || ! docker manifest inspect "$image" > /dev/null 2>&1; then
        # check locally
        pull=""
        image="psalm:internal_stubs_${f}"
        if ! docker image inspect "$image" > /dev/null 2>&1; then
            bash bin/stubs/build_docker.sh local "$f"
        fi
    fi

    MSYS_NO_PATHCONV=1 docker run ${pull:+"--pull" always} --quiet --platform linux/amd64 --rm -v "${PWD}:/app" "$image" php /app/bin/stubs/gen_base_callmap.php "$f" &
done

wait

if [[ ! -d vendor ]]
then
    MSYS_NO_PATHCONV=1 docker run --pull always --quiet --platform linux/amd64 --rm -v "${PWD}:/app" -w /app composer:latest composer install --no-progress
fi

# don't --pull but reuse the image we just downloaded
MSYS_NO_PATHCONV=1 docker run --quiet --platform linux/amd64 --rm -v "${PWD}:/app" -w /app "$image" php /app/bin/stubs/gen_callmap.php
