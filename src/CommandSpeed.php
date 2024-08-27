<?php

declare(strict_types=1);

namespace Oct8pus\hstat;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CommandSpeed extends Command
{
    private SymfonyStyle $style;

    /**
     * Configure command options
     *
     * @return void
     */
    protected function configure() : void
    {
        $this->setName('speed')
            ->setDescription('Measure web page speed')
            ->addArgument('url', InputArgument::REQUIRED)
            ->addOption('iterations', 'i', InputOption::VALUE_REQUIRED, 'number of iterations')
            ->addOption('pause', 'p', InputOption::VALUE_REQUIRED, 'pause in ms between iterations')
            ->addOption('average', 'a', InputOption::VALUE_NONE, 'show average')
            ->addOption('median', 'm', InputOption::VALUE_NONE, 'show median')
            ->addOption('min', '', InputOption::VALUE_NONE, 'show min')
            ->addOption('max', '', InputOption::VALUE_NONE, 'show max')
            ->addOption('arguments', 'r', InputOption::VALUE_REQUIRED, 'arguments to pass to curl', '')
            ->addOption('hide-iterations', '', InputOption::VALUE_NONE, 'hide iterations');
    }

    /**
     * Execute command
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        // beautify input, output interface
        $this->style = new SymfonyStyle($input, $output);

        // check that curl is installed
        if (self::commandExists('curl')) {
            $this->style->writeln('curl command found', OutputInterface::VERBOSITY_VERBOSE);
        } else {
            $this->style->error([
                'curl command is missing',
                'ubuntu: apt install curl',
                'alpine: apk add curl',
            ]);

            return 127;
        }

        // get url argument
        $url = $input->getArgument('url');

        // get iterations option
        $iterations = $input->getOption('iterations');

        // minimum one iteration
        if (!$iterations) {
            $iterations = 1;
        }

        // get pause option
        $pause = $input->getOption('pause');
        $pause = is_numeric($pause) ? (int) $pause : false;

        // get arguments to pass to curl
        $arguments = $input->getOption('arguments');

        if (!is_string($url) || !is_string($arguments)) {
            throw new Exception('invalid url or arguments');
        }

        // build curl command
        $command = self::buildCommand($url, $arguments);

        // log curl command
        $this->style->writeln($command, OutputInterface::VERBOSITY_VERBOSE);

        // loop iterations
        $stats = [];

        for ($i = 0; $i < $iterations; ++$i) {
            $stat = [];

            // measure speed
            if (self::measure($command, $stat)) {
                if (!$i) {
                    // create stats
                    foreach ($stat as $key => $value) {
                        $stats[$key] = [$value];
                    }
                } else {
                    // add stats to existing stats
                    foreach ($stat as $key => $value) {
                        $stats[$key][] = $value;
                    }
                }
            }

            // pause
            if ($iterations > 1 && $pause) {
                usleep($pause * 1000);
            }
        }

        // create table cells
        $cells = [];

        for ($i = 0; $i < $iterations; ++$i) {
            $cells[] = [
                $i + 1,
                $stats['range_dns'][$i],
                $stats['range_connect'][$i],
                $stats['range_ssl'][$i],
                $stats['range_server'][$i],
                $stats['range_transfer'][$i],
                $stats['time_total'][$i],
            ];
        }

        // calculate stats
        if ($input->getOption('median')) {
            $med = self::median($cells);
        }

        if ($input->getOption('average')) {
            $avg = self::average($cells);
        }

        if ($input->getOption('min')) {
            $min = self::min($cells);
        }

        if ($input->getOption('max')) {
            $max = self::max($cells);
        }

        // add stats to cells
        if (isset($med) || isset($avg) || isset($min) || isset($max)) {
            $line = [
                '', '', '', '', '', '',
            ];

            // add separating line
            $cells[] = $line;

            if ($input->getOption('hide-iterations')) {
                // hide iterations from results
                $cells = [];
            }

            // add stats to table
            if (isset($med)) {
                $cells[] = $med;
            }

            if (isset($avg)) {
                $cells[] = $avg;
            }

            if (isset($min)) {
                $cells[] = $min;
            }

            if (isset($max)) {
                $cells[] = $max;
            }
        }

        // create table
        $this->style->table([
            '/',
            'DNS lookup (ms)',
            'TCP connection (ms)',
            'TLS handshake (ms)',
            'server processing (ms)',
            'content transfer (ms)',
            'total (ms)',
        ], $cells);

        return 0;
    }

    /**
     * Measure speed
     *
     * @param string $command
     * @param array<string, string> $stats
     *
     * @return bool true on success, otherwise false
     */
    private function measure(string $command, array &$stats) : bool
    {
        // execute command - taken from httpstat
        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            sys_get_temp_dir(),
            null,
            [
                //'bypass_shell' => true,
            ]
        );

        if (!$process) {
            throw new Exception('open process');
        }

        // get curl stdout and stderr
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        // get curl process status
        $status = proc_get_status($process);
        $exit = $status['exitcode'];

        if ($exit !== 0 && $exit !== -1) {
            $this->style->writeln(sprintf('curl error: %s', $stderr), OutputInterface::VERBOSITY_NORMAL);
            return false;
        }

        // show stderr to user
        $this->style->writeln($stderr, OutputInterface::VERBOSITY_VERBOSE);

        // decode curl json stats
        $stats = json_decode($stdout, true);

        // check for decode errors
        if ($stats === null) {
            $this->style->writeln(sprintf('json decode error: %s', json_last_error_msg()), OutputInterface::VERBOSITY_NORMAL);
            $this->style->writeln(sprintf('curl result: %d %s %s', $exit, $stdout, $stderr), OutputInterface::VERBOSITY_VERBOSE);
            return false;
        }

        if (!is_array($stats)) {
            throw new Exception('decode json');
        }

        // convert timing into ms
        foreach ($stats as $key => $value) {
            $stats[$key] = round($value * 1000, 0);
        }

        // calculate timing - taken from httpstat
        $stats['range_dns'] = $stats['time_namelookup'];
        $stats['range_connect'] = $stats['time_connect'] - $stats['time_namelookup'];
        $stats['range_ssl'] = $stats['time_pretransfer'] - $stats['time_connect'];
        $stats['range_server'] = $stats['time_starttransfer'] - $stats['time_pretransfer'];
        $stats['range_transfer'] = $stats['time_total'] - $stats['time_starttransfer'];

        return true;
    }

    /**
     * Build command
     *
     * @param string $url
     * @param string $arguments arguments to pass to curl
     *
     * @return string command
     */
    private static function buildCommand(string $url, string $arguments) : string
    {
        $command = 'curl';

        // -S, --show-error    Show error even when -s is used
        // -s, --silent        Silent mode
        // -o, --output <file> Write to file instead of stdout
        // -w, --write-out <format> Use output FORMAT after completion
        // -D, --dump-header <filename> Write the received headers to <filename>
        $args = [
            '--silent',
            '--show-error',
            [
                '--output',
                tempnam(sys_get_temp_dir(), 'hstat'),
            ],
            /*[
                '--dump-header',
                tempnam(sys_get_temp_dir(), 'hstat'),
            ],*/
            [
                '--write-out',
                self::buildWriteoutArgument(),
            ],
        ];

        // get quote character based on os
        if (self::isWindows()) {
            $quoteChar = '"';
        } else {
            $quoteChar = '\'';
        }

        $spaceChar = ' ';

        // convert array arguments to string
        foreach ($args as $key => &$values) {
            if (is_array($values)) {
                $output = '';

                foreach ($values as $keyColumn => $value) {
                    if ($keyColumn === 0) {
                        $output = $value;
                    } else {
                        $output .= $spaceChar . $quoteChar . $value . $quoteChar;
                    }
                }

                $args[$key] = $output;
            }
        }

        // build command by imploding arguments
        $command .= $spaceChar . implode($spaceChar, $args);

        // add user passed arguments
        $command .= $spaceChar . $arguments;

        // add url
        return $command . ' -- ' . $url;
    }

    /**
     * Build curl write-out argument
     *
     * @return string
     */
    private static function buildWriteoutArgument() : string
    {
        $params = [
            'time_namelookup',
            'time_connect',
            'time_appconnect',
            'time_pretransfer',
            'time_redirect',
            'time_starttransfer',
            'time_total',
            'speed_download',
            //            'speed_upload',
        ];

        // get quote character based on os
        if (self::isWindows()) {
            $quote = '\"';
        } else {
            $quote = '"';
        }

        $curlParams = '';

        foreach ($params as $i => $param) {
            $curlParams .= $i ? ',' : '';
            $curlParams .= $quote . $param . $quote . ": %{{$param}}";
        }

        return '{' . $curlParams . '}';
    }

    /**
     * Check if command is installed
     *
     * @param string $cmd
     *
     * @return bool true if installed, otherwise false
     */
    private static function commandExists(string $cmd) : bool
    {
        $return = shell_exec(sprintf('which %s', escapeshellarg($cmd)));
        return !empty($return);
    }

    /**
     * Check if operating system is Windows
     *
     * @return bool
     */
    private static function isWindows() : bool
    {
        return strcasecmp(PHP_OS, 'WINNT') === 0;
    }

    /**
     * Calculate average for each array column
     *
     * @param array<int, array<int, int>> $cells
     *
     * @return array<int, int|string>
     */
    private static function average(array $cells) : array
    {
        $avg = [];

        foreach ($cells as $key => $line) {
            foreach ($line as $keyColumn => $value) {
                // set name in iteration column
                if ($keyColumn === 0) {
                    $avg[0] = 'avg';
                    continue;
                }

                // set first value
                if ($key === 0) {
                    $avg[$keyColumn] = $value;
                    continue;
                }

                // add value
                $avg[$keyColumn] += $value;
            }
        }

        $count = count($cells);

        foreach ($avg as $key => &$value) {
            // ignore iteration column
            if ($key === 0) {
                continue;
            }

            // get column average
            $avg[$key] = round($value / $count, 0);
        }

        return $avg;
    }

    /**
     * Calculate median for each array column
     *
     * @param array<int, array<int, int>> $cells
     *
     * @return array<int, int|string>
     */
    private static function median(array $cells) : array
    {
        $med = [];

        $med[0] = 'med';

        // get rows count
        $rows = count($cells);

        // get columns count
        $columns = count($cells[0]);

        // iterate through columns (skip first one)
        for ($i = 1; $i < $columns; ++$i) {
            $column = [];

            // iterate through column rows
            for ($j = 0; $j < $rows; ++$j) {
                // get cell value
                $column[] = $cells[$j][$i];
            }

            // sort column values ascending
            sort($column, SORT_NUMERIC);

            // count column values
            $count = count($column);

            $index = floor($count / 2);

            if ($count % 2) {
                $med[$i] = $column[$index];
            } else {
                $med[$i] = (int) round(($column[$index - 1] + $column[$index]) / 2, 0);
            }
        }

        return $med;
    }

    /**
     * Calculate max for each array column
     *
     * @param array<int, array<int, int>> $cells
     *
     * @return array<int, int|string>
     */
    private static function max(array $cells) : array
    {
        $max = [];

        foreach ($cells as $key => $line) {
            foreach ($line as $keyColumn => $value) {
                // set name in iteration column
                if ($keyColumn === 0) {
                    $max[0] = 'max';
                    continue;
                }

                // set first value
                if ($key === 0) {
                    $max[$keyColumn] = $value;
                    continue;
                }

                // update value only if greater
                if ($value > $max[$keyColumn]) {
                    $max[$keyColumn] = $value;
                }
            }
        }

        return $max;
    }

    /**
     * Calculate min for each array column
     *
     * @param array<int, array<int, int>> $cells
     *
     * @return array<int, int|string>
     */
    private static function min(array $cells) : array
    {
        $min = [];

        foreach ($cells as $key => $line) {
            foreach ($line as $keyColumn => $value) {
                // set name in iteration column
                if ($keyColumn === 0) {
                    $min[0] = 'min';
                    continue;
                }

                // set first value
                if ($key === 0) {
                    $min[$keyColumn] = $value;
                    continue;
                }

                // update value only if smaller
                if ($value < $min[$keyColumn]) {
                    $min[$keyColumn] = $value;
                }
            }
        }

        return $min;
    }
}
