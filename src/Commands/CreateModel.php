<?php

namespace ThaLuffy\Elastic\Commands;

use Illuminate\Console\Command;

class CreateModel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elastic:model {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Elastic model';

    protected $name;

    protected $indicesFolder;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->indicesFolder = config('elastic.indices_folder');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->name = $this->getNameArgument();

        $path = $this->makeDirectory(app_path("$indicesFolder/{$this->name}.php"));

        $this->files->put($path, $this->buildModel());

        $this->info('Successfully created index model at ' . $path);
    }

    private function getNameArgument()
    {
        if (!$this->argument('name')) {
            return $this->ask('Please enter a name for the index model (i.e. UserIndex)');
        }

        return $this->argument('name');
    }

    private function buildModel()
    {
        $replace = [
            ':name'          => $this->name,
            ':indicesFolder' => $this->indicesFolder,
        ];

        $stubPath = __DIR__ . "/../Stubs/IndexModelStub";

        $stub   = $this->files->get($stubPath);

        foreach ($replace as $key => $value) {
            $stub = str_replace($key, $value, $stub);
        }

        return $stub;
    }
}
