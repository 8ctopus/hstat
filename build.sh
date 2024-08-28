# let's pack development dependencies in phar
composer install --no-dev

php src/BuildPhar.php
#php box.phar compile

composer install

$(php -r "file_put_contents('bin/hstat.sha256', hash_file('sha256', 'bin/hstat.phar'));")
