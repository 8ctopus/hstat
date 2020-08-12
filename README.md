hstat is a command line tool to test web pages performance.

It was inspired from the works of @talhasch [php-httpstat](https://github.com/talhasch/php-httpstat) and @reorx [httpstat](https://github.com/reorx/httpstat) and adds a few improvements:
- standalone
- iterations with pause inbetween
- data set analysis: average, median, max and min.

# how to install

    curl -L -o hstat.phar https://github.com/8ctopus/hstat/releases/download/v0.0.6/hstat.phar
    
    # check hash against the one published under releases
    sha256sum hstat.phar
    
    # make phar executable
    chmod +x hstat.phar
    
    # rename phar (optional)
    mv hstat.phar hstat
    
    # move phar to /usr/local/bin/ (optional)
    mv hstat /usr/local/bin/
    

# how to use

### measure website speed 10 iterations, 3 seconds pause inbetween, show median, average, min and max
    $ ./hstat speed --iterations 10 --pause 3000 --median --average --min --max https://octopuslabs.io/
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

### hstat documentation

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
      -r, --arguments=ARGUMENTS    arguments to pass to curl [default: ""]
          --hide-iterations        hide iterations
      -h, --help                   Display this help message
      -q, --quiet                  Do not output any message
      -V, --version                Display this application version
          --ansi                   Force ANSI output
          --no-ansi                Disable ANSI output
      -n, --no-interaction         Do not ask any interactive question
      -v|vv|vvv, --verbose         Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

# how to build phar

    php src/Compiler.php
