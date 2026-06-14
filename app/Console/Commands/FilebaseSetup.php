<?php

namespace App\Console\Commands;

use App\Http\Controllers\Filebase\FilebaseController;
use Illuminate\Console\Command;

class FilebaseSetup extends Command
{
    protected $signature = 'filebase:setup';

    protected $description = 'Initialize the preferences storage with default values';

    public function handle(): void
    {
        FilebaseController::setField('season', date('Y').'/'.(date('Y') + 1));
        FilebaseController::setField('show_inactive_leagues', false);
        FilebaseController::setField('favorite_teams', '');
        FilebaseController::setField('favorite_leagues', '');

        $this->info('Preferences storage initialised at database/filebase/preferences.json');
    }
}
