#!/bin/bash
set -e

# Resolve the mount and generated-file paths from the repository root, regardless of the caller's directory.
cd "$(dirname "$0")/../.."

# Only the versions whose reflected callmaps weren't generated from the images of the committed lock are reflected
# again (see dictionaries/autogen/CallMapInputs.php); the others need no image.
stale_versions="$(php bin/stubs/build_docker.php --stale-plain)"
read -r -a stale <<< "$stale_versions"

if [ "${#stale[@]}" -gt 0 ]; then
    # The ghcr.io namespace with the images of the lock: the "Build docker image for stubs" workflow builds them in
    # the namespace of the repository it runs in.
    if [ -z "${STUBS_IMAGE_OWNER:-}" ]; then
        echo "The reflected callmaps of PHP ${stale[*]} don't match bin/stubs/image_lock.php. Push the branch to let the" \
            "\"Build docker image for stubs\" workflow regenerate them, or set STUBS_IMAGE_OWNER to the ghcr.io namespace" \
            "it built the images in (your GitHub user for a fork)." >&2
        exit 1
    fi

    phpstorm_stubs="$(php bin/stubs/build_docker.php --phpstorm-stubs)"
    pids=()
    for f in "${stale[@]}"; do
        extensions="$(php bin/stubs/build_docker.php --extensions-plain "$f")"
        image="ghcr.io/$STUBS_IMAGE_OWNER/psalm:$(php bin/stubs/build_docker.php --tag "$f")"
        # Git Bash must pass /app and the volume mount unchanged instead of converting them to Windows paths.
        MSYS_NO_PATHCONV=1 docker run --pull missing --platform linux/amd64 --rm -v "$PWD:/app" "$image" \
            php /app/bin/stubs/gen_base_callmap.php "$f" "$extensions" "$phpstorm_stubs" &
        pids+=("$!")
    done

    # Check every child: a bare wait does not propagate each failed extraction to this script's exit status.
    # Never merge or stamp partially refreshed reflection maps after an image or extraction failure.
    failed=0
    for pid in "${pids[@]}"; do
        wait "$pid" || failed=1
    done
    if [ "$failed" -ne 0 ]; then
        exit 1
    fi

    php bin/stubs/build_docker.php --record "${stale[@]}"
fi

php bin/stubs/gen_callmap.php
php bin/stubs/gen_callmap.php
