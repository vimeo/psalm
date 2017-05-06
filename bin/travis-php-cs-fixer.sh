set -e
if [[ $(git diff --name-only $TRAVIS_COMMIT_RANGE) != *".php_cs.dist"* ]];
then
  echo ".php_cs.dist has not changed";
  ./vendor/bin/php-cs-fixer fix --verbose --allow-risky=yes --dry-run --config ./.php_cs.dist -- $(git diff --name-only $TRAVIS_COMMIT_RANGE | grep '\.php$') .php_cs.dist;
else
  echo ".php_cs.dist has changed";
  ./vendor/bin/php-cs-fixer fix --verbose --allow-risky=yes --dry-run --config ./.php_cs.dist;
fi
