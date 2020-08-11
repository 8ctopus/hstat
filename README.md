hstat is a command line tool to test web pages performance.

It was inspired from the works of @talhasch [php-httpstat](https://github.com/talhasch/php-httpstat) and @reorx [httpstat](https://github.com/reorx/httpstat) and adds a few improvements:
- standalone
- iterations with pause inbetween
- data set analysis: average, median, max and min.

# how to install

    curl -L -o hstat.phar https://github.com/8ctopus/hstat/releases/download/v0.0.4/hstat.phar
    
    # check hash against the one published under releases
    sha256sum hstat.phar
    
    # make phar executable
    chmod +x hstat.phar
    
    # rename phar (optional)
    mv hstat.phar hstat
    
    # move phar to /usr/local/bin/ (optional)
    mv hstat /usr/local/bin/
    

# how to use

### measure website speed 10 iterations, 3 seconds pause inbetween, show average and max
    $ ./hstat.phar speed --iterations 10 --pause 3000 --average --max https://octopuslabs.io/

     ----- ----------------- --------------------- -------------------- ------------------------ -----------------------
      /     DNS lookup (ms)   TCP connection (ms)   TLS handshake (ms)   server processing (ms)   content transfer (ms)
     ----- ----------------- --------------------- -------------------- ------------------------ -----------------------
      1     10                103                   187                  120                      1
      2     9                 68                    86                   71                       1
      3     9                 73                    90                   73                       1
      4     10                78                    98                   81                       2
      5     9                 75                    93                   76                       1
      6     10                81                    91                   72                       2
      7     9                 154                   159                  367                      1
      8     10                69                    87                   70                       1
      9     10                77                    93                   73                       2
      10    10                71                    90                   72                       1

      avg   10                85                    107                  108                      1
      max   10                154                   187                  367                      2
     ----- ----------------- --------------------- -------------------- ------------------------ -----------------------

### hstat documentation
    $ ./hstat.phar speed --help
    Description:
      Measure web page speed

    Usage:
      speed [options] [--] <url>

    Arguments:
      url

    Options:
      -i, --iterations=ITERATIONS  number of iterations
      -p, --pause=PAUSE            pause in ms between iterations
      -a, --average                show average
      -m, --median                 show median
          --min                    show min
          --max                    show max
      -h, --help                   Display this help message
      -q, --quiet                  Do not output any message
      -V, --version                Display this application version
          --ansi                   Force ANSI output
          --no-ansi                Disable ANSI output
      -n, --no-interaction         Do not ask any interactive question
      -v|vv|vvv, --verbose         Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

# how to build phar

    php src/Compiler.php
