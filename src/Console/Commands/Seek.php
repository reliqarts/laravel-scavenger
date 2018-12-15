<?php

namespace ReliQArts\Scavenger\Console\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use ReliQArts\Scavenger\DTOs\OptionSet;
use ReliQArts\Scavenger\Exceptions\BadDaemonConfig;
use ReliQArts\Scavenger\Helpers\Config;
use ReliQArts\Scavenger\Services\Seeker;

class Seek extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scavenger:seek 
                            {target? : Optionally specify a single target from list of available targets}
                            {--w|keywords= : Comma seperated keywords}
                            {--k|keep : Whether to save found scraps}
                            {--c|convert : Whether to convert found scraps to target objects}
                            {--y|y : Whether to skip confirmation}
                            {--b|backoff=0 : Wait time after each scrape}
                            {--p|pages=2 : Max. number of pages to scrape}
                            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Finds entities and updates database';

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws Exception
     */
    public function handle()
    {
        $saveScraps = $this->option('keep');
        $target = $this->argument('target');
        $keywords = $this->option('keywords');
        $skipConfirmation = $this->option('y');
        $backOff = (int)$this->option('backoff');
        $pages = (int)$this->option('pages');
        $convertScraps = $this->option('convert');
        $optionSet = new OptionSet($saveScraps, $convertScraps, $backOff, $pages, $keywords);
        $seeker = new Seeker($optionSet);
        $confirmationQuestion = "Scavenger will scour a resource for scraps and make model records,"
            . "performing HTTP, DB and I/O operations.\n Ensure your internet connection is stable. Ready?";

        $this->comment(
            PHP_EOL
            . "<info>♣♣♣</info> Scavenger Seek v1.0 \nHelp is here, try: php artisan scavenger:seek --help"
        );

        if ($skipConfirmation || $this->confirm($confirmationQuestion)) {
            try {
                // get scavenger daemon
                $daemon = Config::getDaemon();
            } catch (BadDaemonConfig $e) {
                // fail, could not create daemon user
                $this->line(
                    PHP_EOL
                    . "<error>✘ Woe there! Scavenger daemon doesn't live in your database and couldn't be created. "
                    . "You sure you know what yer doin'?</error>\n► "
                    . $e->getMessage()
                );

                return;
            }

            // log in as daemon
            auth()->login($daemon);

            $this->info(
                "Scavenger is seeking. Output is shown below.\nT: "
                . Carbon::now()->toCookieString() . "\n----------"
            );

            // Seek
            $result = $seeker->seek($target, $this);
            if ($result->success) {
                $this->info(PHP_EOL . '----------');
                $this->comment('<info>✔</info> Done. Scavenger daemon now goes to sleep...');

                try {
                    // Display results
                    $this->line('');
                    $headers = ['Time', 'Scraps Found', 'New', 'Saved?', 'Converted?'];
                    $data = [
                        [
                            $result->extra->executionTime,
                            $result->extra->total,
                            $result->extra->new,
                            $saveScraps ? 'true' : 'false',
                            $convertScraps ? 'true' : 'false',
                        ],
                    ];
                    $this->table($headers, $data);
                    $this->line(PHP_EOL);
                } catch (Exception $ex) {
                    $this->line(
                        PHP_EOL
                        . "<error>✘</error> Something strange happened at the end there... {$ex->getMessage()}"
                    );
                }
            } else {
                $this->line(PHP_EOL . "<error>✘</error> {$result->error}");
            }
        }
    }
}
