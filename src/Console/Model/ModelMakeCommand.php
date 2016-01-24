<?php

namespace duxet\Rethinkdb\Console\Model;

use Illuminate\Foundation\Console\ModelMakeCommand as LaravelMakeModelCommand;

class ModelMakeCommand extends LaravelMakeModelCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:rethink-model';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Rethinkdb model class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Model';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        if (parent::fire() !== false) {
            if ($this->option('migration')) {
                $table = Str::plural(Str::snake(class_basename($this->argument('name'))));

                $this->call('make:rethink-migration', ['name' => "create_{$table}_table", '--create' => $table]);
            }
        }
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['migration', 'm', InputOption::VALUE_NONE, 'Create a new migration file for the model.'],
        ];
    }
}