#!/bin/bash -e

VERSIONS="7.0 7.1 7.2 7.3 7.4 8.0 8.1 8.2 8.3 8.4 8.5"


for f in $VERSIONS; do
    docker run --pull always --platform linux/amd64 --rm -v $PWD:/app ghcr.io/danog/psalm:internal_stubs_$f php /app/bin/stubs/gen_base_callmap.php &
done

wait

php bin/stubs/gen_callmap.php
php bin/stubs/gen_callmap.php
