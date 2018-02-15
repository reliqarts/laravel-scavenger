<?php

namespace ReliQArts\Scavenger\Console\Commands;

use Exception;
use PDOException;
use Carbon\Carbon;
use Illuminate\Console\Command;
use ReliQArts\Scavenger\Helpers\CoreHelper as Helper;
use ReliQArts\Scavenger\Contracts\Seeker as SeekerInterface;

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
     * Seeker instance.
     */
    protected $seeker = null;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(SeekerInterface $seeker)
    {
        parent::__construct();

        $this->seeker = $seeker;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $keep = $this->option('keep');
        $target = $this->argument('target');
        $keywords = $this->option('keywords');
        $skipConfirmation = $this->option('y');
        $backOff = (int) $this->option('backoff');
        $pageLimit = (int) $this->option('pages');
        $convert = $this->option('convert');

        $this->comment(PHP_EOL."<info>♣♣♣</info> Scavenger Seek v1.0 \nHelp is here, try: php artisan scavenger:seek --help");

        if ($skipConfirmation || $this->confirm("Scavenger will scour a resource for scraps and make model records, performing HTTP, DB and I/O operations. \n Ensure your internet connection is stable. Ready?")) {
            
            try {
                // get scavenger daemon
                $daemon = Helper::getDaemon();
            } catch (DaemonException $e) {
                // fail, could not create daemon user
                return $this->line(PHP_EOL."<error>✘ Woe there! Scavenger daemon doesn't live in your database and couldn't be created. You sure you know what yer doin'?</error>\n► " . $e->getMessage());
            }

            // log in as daemon
            auth()->login($daemon);

            $this->info("Scavenger is seeking. Output is shown below.\nT: ".Carbon::now()->toCookieString()."\n----------");

            // Seek
            $seek = $this->seeker->seek($target, $keep, $keywords, $convert, $backOff, $pageLimit, $this);
            if ($seek->success) {
                $this->info(PHP_EOL.'----------');
                $this->comment('<info>✔</info> Done. Scavenger daemon now goes to sleep...');

                try {
                    // Display results
                    $this->line('');
                    $headers = ['Time', 'Scraps Found', 'New', 'Saved?', 'Converted?'];
                    $data = [[$seek->extra->executionTime, $seek->extra->total, $seek->extra->new, $keep ? 'true' : 'false', $convert ? 'true' : 'false']];
                    $this->table($headers, $data);
                    $this->line(PHP_EOL);
                } catch (Exception $ex) {
                    $this->line(PHP_EOL . "<error>✘</error> Something strange happened at the end there... {$ex->getMessage()}");
                }

            } else {
                $this->line(PHP_EOL."<error>✘</error> $seek->error");
            }
        }
    }
}
