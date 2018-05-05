#!/usr/bin/env bash
composer bin box install

vendor/bin/box compile

build/psalm.phar --config=bin/phar.psalm.xml
