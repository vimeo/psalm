#!/bin/bash -e

VERSIONS="8.0 8.1 8.2 8.3 8.4"

for f in $VERSIONS; do
    docker build --build-arg VERSION=$f . -f bin/Dockerfile -t psalm_test_$f &
done

wait

for f in $VERSIONS; do
    docker run --rm -it -v $PWD:/app psalm_test_$f php /app/bin/gen_base_callmap.php
done
