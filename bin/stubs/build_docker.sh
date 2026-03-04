#!/bin/bash -e
# $1: optional: default "local". Github user name, if container image should be published to container registry
# $2: optional: PHP version to build, true to rebuild all, false to rebuild missing only

ACTOR="${1:-local}"
rebuild="${2:-false}"

is_first=true
for file in bin/stubs/Dockerfile_*; do
    # if none found - use shopt -s nullglob alternatively
    if [[ ! -e "$file" ]]; then
        break
    fi
    
    f="${file#bin/stubs/Dockerfile_}"
    if [[ "$rebuild" != true ]] && [[ "$rebuild" != false ]] && [[ "$rebuild" != "$f" ]]; then
        continue
    fi
        
    if [[ "$ACTOR" == "local" ]]
    then
        if [[ "$rebuild" == false ]] && docker image inspect "psalm:internal_stubs_${f}" > /dev/null 2>&1; then
            continue
        fi

        if [[ "$rebuild" == false ]] && docker manifest inspect "ghcr.io/danog/psalm:internal_stubs_${f}" > /dev/null 2>&1; then
            continue
        fi
    
        docker buildx build --load --build-arg VERSION="$f" . -f "bin/stubs/Dockerfile_${f}" -t "psalm:internal_stubs_${f}" &
    elif [[ "$rebuild" == false ]] && docker manifest inspect "ghcr.io/${ACTOR}/psalm:internal_stubs_${f}" > /dev/null 2>&1; then
        continue
    else
        docker buildx build --push --build-arg VERSION="$f" . -f "bin/stubs/Dockerfile_${f}" -t "ghcr.io/${ACTOR}/psalm:internal_stubs_${f}" &
    fi
    
    # run 2 at once at max
    if [[ "$is_first" == true ]]; then
        is_first=false
    else
        wait -n
    fi
done

wait
