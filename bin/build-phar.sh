#!/usr/bin/env bash
if ! php -r 'extension_loaded("bz2") or exit(1);' ; then
	echo "You need to install (or enable) bz2 php extension"
	exit 1
fi

composer bin box install

vendor/bin/box compile

bin/psalm.phar  --config=bin/phar.psalm.xml --root=src
