#!/bin/bash -e

if [ "$PLATFORM" != "ubuntu-latest" ]; then exit 0; fi

VERSIONS="7.0 7.1 7.2 7.3 7.4 8.0 8.1 8.2 8.3 8.4"

for f in $VERSIONS; do
    {
        docker build --build-arg VERSION=$f . -f bin/stubs/Dockerfile_$f -t ghcr.io/$ACTOR/psalm:internal_stubs_$f
    } &
    if [ "$f" == "7.1" ] || [ "$f" == "7.3" ] || [ "$f" == "8.0" ] || [ "$f" == "8.2" ] || [ "$f" == "8.4" ]; then wait; fi
done

wait