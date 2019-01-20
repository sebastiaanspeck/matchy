<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;

class GenerateUserPreferences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:user_preferences {--force : Force to override the current UUID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrate:fresh and db:seed in one command.
  It also displays your new generated uuid.
  <fg=red>Be aware that your preferences will be reset to defaults</>';

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
     * @return void
     */
    public function handle()
    {
        if ($this->option('force') || $this->confirm('Do you wish to generate a new UUID and overwrite the existing one?')) {
            $this->generate();
            exit;
        }

        $this->comment('There is no new UUID generated.');
    }

    private function generate()
    {
        try {
            $this->callSilent('migrate:fresh');
            $this->callSilent('db:seed');
        } catch (QueryException $e) {
            $this->line('Whoops. Something went wrong.', 'fg=red');
            $this->error($e->getMessage());
            exit;
        }

        $this->info('Your new uuid is generated.');
    }
}
