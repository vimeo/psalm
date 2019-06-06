#!/usr/bin/env bash

set -e

composer bin box install

vendor/bin/box compile

if [[ "$GPG_ENCRYPTION" != '' ]] ; then
    echo $GPG_ENCRYPTION | gpg --passphrase-fd 0 keys.asc.gpg
    gpg --batch --yes --import keys.asc
    echo $SIGNING_KEY | gpg --passphrase-fd 0 -u 8A03EA3B385DBAA1 --armor --detach-sig build/psalm.phar
fi
