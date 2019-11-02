<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Class FilebaseSetup.
 */
class FilebaseSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'filebase:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup the filebase';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @throws \Filebase\Filesystem\FilesystemException
     *
     * @return mixed
     */
    public function handle()
    {
        $db = new \Filebase\Database(['dir' => 'database/filebase']);

        $preferences = $db->get('preferences');

        $preferences->season = '2018/2019';
        $preferences->show_inactive_leagues = false;
        $preferences->favorite_teams = '';
        $preferences->favorite_leagues = '';

        $preferences->save();

        dump($db);
        dump($preferences);
    }
}
