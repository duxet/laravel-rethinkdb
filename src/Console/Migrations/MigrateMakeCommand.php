<?php

namespace duxet\Rethinkdb\Console\Migrations;

use duxet\Rethinkdb\Migrations\MigrationCreator;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand as LaravelMigration;
use Illuminate\Support\Composer;

class MigrateMakeCommand extends LaravelMigration
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'make:rethink-migration {name : The name of the migration.}
        {--create= : The table to be created.}
        {--table= : The table to migrate.}
        {--path= : The location where the migration file should be created.}';

    /**
     * Create a new migration install command instance.
     *
     * @param duxet\Rethinkdb\Migrations\MigrationCreator $creator
     * @param \Illuminate\Support\Composer                $composer
     *
     * @return void
     */
    public function __construct(MigrationCreator $creator, Composer $composer)
    {
        parent::__construct($creator, $composer);
    }
}
