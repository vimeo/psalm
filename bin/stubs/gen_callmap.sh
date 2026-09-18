#!/bin/bash -e

# Namespace the stub images live under (ghcr.io/<owner>/psalm). Overridable so CI
# can point at the images it just built; defaults to the maintainer's namespace.
OWNER="${STUBS_IMAGE_OWNER:-danog}"

# Optional tag suffix, so a non-canonical branch build uses its own branch-scoped
# images (must match TAG_SUFFIX passed to build_docker.php). Empty by default.
SUFFIX="${STUBS_IMAGE_TAG_SUFFIX:-}"

# Single source of truth for the version list (see build_docker.php).
VERSIONS="$(php "$(dirname "$0")/build_docker.php" --versions-plain)"

for f in $VERSIONS; do
    docker run --pull always --platform linux/amd64 --rm -v $PWD:/app ghcr.io/$OWNER/psalm:internal_stubs_$f${SUFFIX:+-$SUFFIX} php /app/bin/stubs/gen_base_callmap.php &
done

wait

php bin/stubs/gen_callmap.php
php bin/stubs/gen_callmap.php
