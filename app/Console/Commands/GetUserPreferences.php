<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class GetUserPreferences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:user_preferences';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show your user preferences';

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
        $user = $this->getUserPreferences();

        $user_favorite_leagues = $this->getUserFavorites('leagues');
        $user_favorite_teams = $this->getUserFavorites('teams');

        $user_preferences = $this->convertToOneArray($user, $user_favorite_leagues, $user_favorite_teams);

        $this->table(array_keys($user_preferences), [$user_preferences]);
    }

    private function getUserPreferences()
    {
        try {
            $user_preferences = \App\UserPreferences::firstOrFail()->getAttributes();
        } catch (ModelNotFoundException $e) {
            $this->line('The user-preferences for the UUID in the .env-file could not be found. 
You can solve it, by running `generate:user_preferences`. Be aware that your preferences will be reset to defaults.', 'fg=red');
            exit;
        } catch (QueryException $e) {
            $this->line('Whoops. Something went wrong.', 'fg=red');
            $this->error($e->getMessage());
            exit;
        }

        return $user_preferences;
    }

    private function getUserFavorites($type)
    {
        return \DB::table('favorite_'.$type)->where('user_uuid', env('UUID'))->get();
    }

    private function convertToOneArray($user_preferences, $favorite_leagues, $favorite_teams)
    {
        $user_preferences['show_inactive_leagues'] === 1 ? $user_preferences['show_inactive_leagues'] = 'True' : $user_preferences['show_inactive_leagues'] = 'False';

        $user_preferences['leagues'] = $this->createString($favorite_leagues);
        $user_preferences['teams'] = $this->createString($favorite_teams);

        return $user_preferences;
    }

    private function createString($favorites)
    {
        $ids = [];

        foreach($favorites as $favorite) {
            array_push($ids, $favorite->id);
        }

        asort($ids);

        return implode(', ', $ids);
    }
}
