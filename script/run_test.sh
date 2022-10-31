if [ -d $PWD/script ]; then
  chmod +x $PWD/script/composer.phar
  chmod +x $PWD/script/phpunit.phar
  $PWD/script/composer.phar update
  $PWD/script/composer.phar install
  $PWD/script/phpunit.phar test/Test --testdox > ./test/result.txt
else
  chmod +x composer.phar
  chmod +x phpunit.phar
  ./composer.phar update
  ./composer.phar install --working-dir ../
  ./phpunit.phar ../test/Test/PutObjectACLTest.php --testdox > ../test/result.txt
fi




