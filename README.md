# hstat

[![license](https://poser.pugx.org/8ctopus/hstat/license)](https://packagist.org/packages/8ctopus/hstat)
![lines of code](https://raw.githubusercontent.com/8ctopus/hstat/image-data/lines.svg)

hstat is a command line tool to test the performance of webpages.

It was inspired from the works of @talhasch [php-httpstat](https://github.com/talhasch/php-httpstat) and @reorx [httpstat](https://github.com/reorx/httpstat) and adds a few improvements:

- standalone
- iterations with pause in between
- data set analysis: average, median, max and min.

## how to install

You have the choice between:

- composer install `composer require 8ctopus/hstat`
- download the phar
- or build it yourself

```sh
curl -LO https://github.com/8ctopus/hstat/releases/download/1.1.1/hstat.phar

# check hash against the one published under releases
sha256sum hstat.phar

# make phar executable
chmod +x hstat.phar

# rename phar (optional)
mv hstat.phar hstat

# move phar to /usr/local/bin/ (optional)
mv hstat /usr/local/bin/
```

## how to use

### basic

```txt
$ hstat speed https://octopuslabs.io/

 --- ----------------- --------------------- -------------------- ------------------------ ----------------------- ------------
  /   DNS lookup (ms)   TCP connection (ms)   TLS handshake (ms)   server processing (ms)   content transfer (ms)   total (ms)
 --- ----------------- --------------------- -------------------- ------------------------ ----------------------- ------------
  1   16                237                   767                  156                      0                       1176
 --- ----------------- --------------------- -------------------- ------------------------ ----------------------- ------------
```

### advanced

measure website speed 10 iterations, 3 seconds pause in between, show median, average, min and max

```txt
$ hstat speed --iterations 10 --pause 3000 --median --average --min --max https://octopuslabs.io/
 ----- ----------------- --------------------- -------------------- ------------------------ -----------------------
  /     DNS lookup (ms)   TCP connection (ms)   TLS handshake (ms)   server processing (ms)   content transfer (ms)
 ----- ----------------- --------------------- -------------------- ------------------------ -----------------------
  1     21                69                    96                   67                       1
  2     14                74                    102                  73                       2
  3     12                114                   93                   69                       0
  4     21                68                    106                  68                       1
  5     14                92                    97                   67                       1
  6     28                72                    132                  364                      1
  7     22                72                    99                   72                       1
  8     14                86                    110                  69                       1
  9     8                 102                   92                   74                       1
  10    12                67                    87                   68                       1

  med   14                73                    98                   69                       1
  avg   17                82                    101                  99                       1
  min   8                 67                    87                   67                       0
  max   28                114                   132                  364                      2
 ----- ----------------- --------------------- -------------------- ------------------------ -----------------------
```

__NOTE__: when testing a website with a self-signed SSL certificate, add the argument `--arguments="--insecure"`

#### definitions

- DNS lookup : time to lookup the server's IP address
- TCP connection : time to establish the connection with the server
- TLS handshake : time to establish a secured connection between you and the server (for https only)
- server processing : time the server took to process the request (apache/nginx + php)
- content transfer : time to transfer the page to you

#### Xdebug cookie example

```bash
hstat speed --iterations 10 --median --average --min --max --arguments="--cookie \"XDEBUG_SESSION=mysession\"" https://octopuslabs.io/
```

## build phar

```bash
./build.sh
```

## improvement ideas

- add headers option
- add specific header option
- make speed command default command
- export to csv
- parallel curl requests
- make comparisons possible
- fix json_decode locale issue with curl command - https://github.com/curl/curl/issues/1037
- remove TLS column when http request
- support for concomitant requests
- more stats
- add test progress indicator
