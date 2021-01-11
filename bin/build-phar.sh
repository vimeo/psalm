#!/usr/bin/env bash

set -e

composer bin box install

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

php $DIR/improve_class_alias.php

vendor/bin/box compile

if [[ "$GPG_SIGNING" != '' ]] ; then
    echo "$SIGNING_KEY" | gpg --import --no-tty --batch --yes
    gpg --command-fd 0 --pinentry-mode loopback -u "$SIGNING_KEY_ID" --batch --detach-sign --armor --output build/psalm.phar.asc build/psalm.phar
fi
