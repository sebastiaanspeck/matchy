<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserPreferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->uuid('uuid');
            $table->string('name');
            $table->boolean('show_inactive_leagues')->default(false);
            $table->string('current_season');
        });

        Schema::create('favorite_leagues', function (Blueprint $table) {
            $table->uuid('user_uuid');
            $table->string('id');
        });

        Schema::create('favorite_teams', function (Blueprint $table) {
            $table->uuid('user_uuid');
            $table->string('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_preferences');
        Schema::dropIfExists('favorite_leagues');
        Schema::dropIfExists('favorite_teams');
    }
}
