#!/usr/bin/env bash

set -e
shopt -s extglob # required for the rm -rf below

git clone https://${GITHUB_TOKEN}@github.com/psalm/phar.git > /dev/null 2>&1
cd phar
rm -rf -- !(".git")
cp ../build/psalm.phar ../build/psalm.phar.asc ../assets/psalm-phar/* .
mv dot-gitignore .gitignore
git config user.email "travis@travis-ci.org"
git config user.name "Travis CI"
git add --all .
git commit -m "Updated Psalm phar to commit ${TRAVIS_COMMIT}"
git push --quiet origin master

if [[ "$TRAVIS_TAG" != '' ]] ; then
    git tag "$TRAVIS_TAG"
    git push origin "$TRAVIS_TAG"
fi
