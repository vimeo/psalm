#!/usr/bin/env bash

set -e

php -v

composer bin box install

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

php $DIR/improve_class_alias.php

php -r 'require "vendor/autoload.php"; Psalm\Internal\VersionUtils::dump();'
if [[ ! -f build/phar-versions.php ]] ; then
   echo "failed to dump versions";
   exit;
fi

vendor/bin/box compile --no-parallel

rm -rf /tmp/psalmTest
mkdir -p /tmp/psalmTest
cd /tmp/psalmTest
composer require danog/loop # Just to gen the composer.json

$OLDPWD/build/psalm.phar -v

cd $OLDPWD
rm -rf /tmp/psalmTest

if [[ "$GPG_SIGNING" != '' ]] ; then
    if [[ "$GPG_SECRET_KEY" != '' ]] ; then
        echo "Load secret key into gpg"
        echo "$GPG_SECRET_KEY" | gpg --import --no-tty --batch --yes
    fi

    echo "Sign Phar"

    echo "$GPG_PASSPHRASE" | gpg --command-fd 0 --passphrase-fd 0 --pinentry-mode loopback -u 99BF4D9A33D65E1E --batch --detach-sign --armor --output build/psalm.phar.asc build/psalm.phar
fi
