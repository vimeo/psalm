#!/bin/bash -e

VERSIONS="7.0 7.1 7.2 7.3 7.4 8.0 8.1 8.2 8.3 8.4 8.5"
LAST="${VERSIONS##* }"

for f in $VERSIONS; do
    MSYS_NO_PATHCONV=1 docker run --pull always --quiet --platform linux/amd64 --rm -v "${PWD}:/app" "ghcr.io/danog/psalm:internal_stubs_${f}" php /app/bin/stubs/gen_base_callmap.php "$f" &

    if [[ "$f" == "$LAST" ]]; then
        wait

        MSYS_NO_PATHCONV=1 docker run --pull always --quiet --platform linux/amd64 --rm -v "${PWD}:/app" "ghcr.io/danog/psalm:internal_stubs_${f}" php /app/bin/stubs/gen_callmap.php
    fi
done
