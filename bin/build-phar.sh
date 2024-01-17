#!/usr/bin/env bash

set -e

composer bin box install

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

php $DIR/improve_class_alias.php

php -r 'require "vendor/autoload.php"; Psalm\Internal\VersionUtils::dump();'
if [[ ! -f build/phar-versions.php ]] ; then
   echo "failed to dump versions";
   exit;
fi

vendor/bin/box compile --no-parallel

if [[ "$GPG_SIGNING" != '' ]] ; then
    if [[ "$GPG_SECRET_KEY" != '' ]] ; then
        echo "Load secret key into gpg"
        echo "$GPG_SECRET_KEY" | gpg --import --no-tty --batch --yes
    fi

    echo "Sign Phar"

    echo "$GPG_PASSPHRASE" | gpg --command-fd 0 --passphrase-fd 0 --pinentry-mode loopback -u 12CE0F1D262429A5 --batch --detach-sign --armor --output build/psalm.phar.asc build/psalm.phar
fi
