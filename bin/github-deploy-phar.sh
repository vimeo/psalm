#!/usr/bin/env bash

set -e


if [[ ${GITHUB_REPOSITORY} != 'vimeo/psalm'  &&  -z ${PHAR_REPO_SLUG} ]]; then
    echo 'Not attempting phar deployment, as this is not vimeo/psalm, and $PHAR_REPO_SLUG is unset or empty'
    exit 0;
fi;

PHAR_REPO_SLUG=${PHAR_REPO_SLUG:=psalm/phar}

git clone https://${PHAR_REPO_TOKEN}@github.com/${PHAR_REPO_SLUG}.git phar > /dev/null 2>&1

set -x # don't do set x above this point to protect the GITHUB_TOKEN

cd phar
rm -rf *
cp ../build/psalm.phar ../assets/psalm-phar/* .
cp ../build/psalm.phar.asc || true # not all users have GPG keys
mv dot-gitignore .gitignore
git config user.email "github@muglug.com"
git config user.name "Automated commit"
git add --all .
git commit -m "Updated Psalm phar to commit ${GITHUB_SHA}"
git push --quiet origin master > /dev/null 2>&1

if [[ "$GITHUB_REF" != '' && "$GITHUB_REF" != 'refs/heads/master' ]] ; then
    git tag "$GITHUB_REF"
    git push origin "$GITHUB_REF"
fi
