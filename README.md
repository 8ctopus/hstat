hstat is a command line tool to test web pages performance.

It was inspired from the works of @talhasch [php-httpstat](https://github.com/talhasch/php-httpstat) and @reorx [httpstat](https://github.com/reorx/httpstat).

# how to install

    curl -L -o hstat.phar https://github.com/8ctopus/hstat/releases/download/v0.0.1/hstat.phar
    
    # check hash against the one published under releases
    sha256sum hstat.phar
    
    # make phar executable
    chmod +x hstat.phar
    
    # rename phar (optional)
    mv hstat.phar hstat
    
    # move phar to /usr/local/bin/ (optional)
    mv hstat /usr/local/bin/
    

# how to use

    $ ./hstat speed --iterations 10 --pause 3000 https://octopuslabs.io/
    
     [OK]
    
     ---- ----------------- --------------------- -------------------- ------------------------ -----------------------
      i    DNS lookup (ms)   TCP connection (ms)   TLS handshake (ms)   server processing (ms)   content transfer (ms)
     ---- ----------------- --------------------- -------------------- ------------------------ -----------------------
      1    96                81                    114                  84                       190
      2    99                76                    118                  78                       196
      3    94                82                    122                  199                      150
      4    14                84                    107                  108                      145
      5    16                77                    132                  79                       154
      6    107               79                    112                  90                       157
      7    92                74                    114                  75                       149
      8    90                87                    122                  88                       175
      9    90                92                    101                  78                       154
      10   144               92                    117                  80                       189
     ---- ----------------- --------------------- -------------------- ------------------------ -----------------------

# build phar

    php src/Compiler.php
