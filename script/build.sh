#! /usr/bin/env bash

if [ -d $PWD/script ]; then
  rm -rf $PWD/composer.lock $PWD/vendor/
  version=$(grep 'private $version' $PWD/src/Config/ConfigParser.php | grep -oE '[0-9.]+')
else
  rm -rf composer.lock vendor/
  version=$(grep 'private $version' src/Config/ConfigParser.php | grep -oE '[0-9.]+')
fi

composer install --no-dev

phar pack -f ve-tos-php-sdk-$version.phar -x '(.idea|.git|test|script)' -c none .