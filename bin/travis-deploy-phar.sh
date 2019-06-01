#!/usr/bin/env bash

git clone https://${GITHUB_TOKEN}@github.com/psalm/phar.git > /dev/null 2>&1
cp build/psalm.phar build/psalm.phar.asc phar/
cd phar
git config user.email "travis@travis-ci.org"
git config user.name "Travis CI"
git add psalm.phar psalm.phar.asc
git commit -m "Updated Psalm phar to commit ${TRAVIS_COMMIT}"
git push --quiet origin master

if [[ "$TRAVIS_TAG" != '' ]] ; then
    git tag "$TRAVIS_TAG"
    git push origin "$TRAVIS_TAG"
fi
