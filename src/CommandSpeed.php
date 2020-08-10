<?php declare(strict_types=1);

/**
 * @author 8ctopus <hello@octopuslabs.io>
 */

namespace Oct8pus\hstat;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CommandSpeed extends Command
{
    private $io;

    /**
     * Configure command options
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('speed')
            ->setDescription('Measure web page speed')
            ->addArgument('url', InputArgument::REQUIRED)
            ->addOption('iterations', 'i', InputOption::VALUE_REQUIRED, 'number of iterations')
            ->addOption('pause', 'p', InputOption::VALUE_REQUIRED, 'pause in ms between iterations');
    }

    /**
     * Execute command
     * @param  InputInterface $input
     * @param  OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // beautify input, output interface
        $this->io = new SymfonyStyle($input, $output);

        // check that curl is installed
        if (self::command_exists('curl'))
            $this->io->writeln('curl command found', OutputInterface::VERBOSITY_VERBOSE);
        else {
            $this->io->error([
                'curl command is missing',
                'ubuntu: apt install curl',
                'alpine: apk add curl'
            ]);

            return 127;
        }

        // get url argument
        $url = $input->getArgument('url');

        // get iterations option
        $iterations = $input->getOption('iterations');

        // minimum one iteration
        if (!$iterations)
            $iterations = 1;

        // get pause option
        $pause = $input->getOption('pause');

        // convert pause to int
        if ($pause !== false)
            $pause = intval($pause);

        // loop iterations
        $stats = [];

        for ($i = 0; $i < $iterations; ++$i) {
            $stat = [];

            // measure speed
            if (self::measure($url, $stat))
                if (!$i) {
                    // create stats
                    foreach ($stat as $key => $value) {
                        $stats[$key] = [$value];
                    }
                }
                else
                    // add stats to existing stats
                    foreach ($stat as $key => $value) {
                        array_push($stats[$key], $value);
                    }

            // pause
            if ($pause)
                usleep($pause * 1000);
        }

        // log success
        $this->io->newLine(2);
        $this->io->success('');

        // create table cells
        $cells = [];

        for ($i = 0; $i < $iterations; ++$i) {
            array_push($cells, [
                $i + 1,
                $stats['range_dns'][$i],
                $stats['range_connect'][$i],
                $stats['range_ssl'][$i],
                $stats['range_server'][$i],
                $stats['range_transfer'][$i],
            ]);
        }

        // TODO create average

        // create table
        $this->io->table([
            'i',
            'DNS lookup (ms)',
            'TCP connection (ms)',
            'TLS handshake (ms)',
            'server processing (ms)',
            'content transfer (ms)',
        ], $cells);

        return 0;
    }

    /**
     * Measure speed
     * @param  string $url
     * @param [out] array $stats
     * @return bool true on success, otherwise false
     */
    private function measure(string $url, array &$stats): bool
    {
        // get curl parameters to track speed
        $params = self::build_curl_argument_w();

        // get temporary filenames
        $file_headers = tempnam(sys_get_temp_dir(), 'hstat');
        $file_body    = tempnam(sys_get_temp_dir(), 'hstat');

        // curl command
        $command = "curl -s -S -w {$params} -o {$file_body} -D {$file_headers} {$url}";

        // log curl command
        $this->io->writeln($command, OutputInterface::VERBOSITY_VERBOSE);

        // execute command - taken from httpstat
        $process = proc_open($command, [
                0 => array("pipe", "r"),
                1 => array("pipe", "w"),
                2 => array("pipe", "w"),
            ],
            $pipes,
            sys_get_temp_dir(),
            null,
            [
                //'bypass_shell' => true,
        ]);

        // get curl stdout and stderr
        $std_out = stream_get_contents($pipes[1]);
        $std_err = stream_get_contents($pipes[2]);

        // get curl process status
        $status = proc_get_status($process);
        $exit   = $status['exitcode'];

        if ($exit != 0 && $exit != -1) {
            $this->io->writeln(sprintf('curl error: %s', $std_err), OutputInterface::VERBOSITY_NORMAL);
            return false;
        }

        // show stderr to user
        $this->io->writeln($std_err, OutputInterface::VERBOSITY_VERBOSE);

        // decode curl json stats
        $stats = json_decode($std_out, true);

        // check for decode errors
        if ($stats === null) {
            $this->io->writeln(sprintf('json decode error: %s', json_last_error_msg()), OutputInterface::VERBOSITY_NORMAL);
            $this->io->writeln(sprintf('curl result: %d %s %s', $exit, $std_out, $std_err), OutputInterface::VERBOSITY_VERBOSE);
            return false;
        }

        // convert timing into ms
        foreach ($stats as $key => $value) {
            $stats[$key] = round($value * 1000, 0);
        }

        // calculate timing - taken from httpstat
        $stats['range_dns']      = $stats['time_namelookup'];
        $stats['range_connect']  = $stats['time_connect']       - $stats['time_namelookup'];
        $stats['range_ssl']      = $stats['time_pretransfer']   - $stats['time_connect'];
        $stats['range_server']   = $stats['time_starttransfer'] - $stats['time_pretransfer'];
        $stats['range_transfer'] = $stats['time_total']         - $stats['time_starttransfer'];

        return true;
    }

    /**
     * Build curl argument -w
     * @return string
     */
    private static function build_curl_argument_w(): string
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

        $quote       = '\"';
        $curl_params = '';

        foreach ($params as $i => $param) {
            $curl_params .= $i ? ',' : '';
            $curl_params .= $quote . $param . $quote .": %{{$param}}";
        }

        return '"{'. $curl_params .'}"';
    }

    /**
     * Check if command is installed
     * @param  string $cmd
     * @return bool true if installed, otherwise false
     */
    public static function command_exists(string $cmd): bool
    {
        $return = shell_exec(sprintf("which %s", escapeshellarg($cmd)));
        return !empty($return);
    }
}
