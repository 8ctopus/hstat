php src/BuildPhar.php
#php box.phar compile

$(php -r "file_put_contents('bin/hstat.sha256', hash_file('sha256', 'bin/hstat.phar'));")
