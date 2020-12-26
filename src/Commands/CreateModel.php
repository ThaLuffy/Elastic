<?php

namespace ThaLuffy\Elastic\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class CreateModel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elastic:model {name?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Elastic model';

    protected $name;

    protected $indexFolder;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;

        $this->indexFolder = config('elastic.index_folders')[0] ?? 'Indices';
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->name = $this->__getNameArgument();

        $path = $this->__makeDirectory(app_path("{$this->indexFolder}/{$this->name}.php"));

        $this->files->put($path, $this->__buildModel());

        $this->info('Successfully created index model at ' . $path);
    }

    private function __makeDirectory($path)
    {
        $directory = dirname($path);

        if (!$this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true, true);
        }

        return $path;
    }

    private function __getNameArgument()
    {
        if (!$this->argument('name')) {
            return $this->ask('Please enter a name for the index model (i.e. UserIndex)');
        }

        return $this->argument('name');
    }

    private function __buildModel()
    {
        $replace = [
            ':name'          => $this->name,
            ':indexFolder' => $this->indexFolder,
        ];

        $stubPath = __DIR__ . "/../Stubs/IndexModelStub";

        $stub   = $this->files->get($stubPath);

        foreach ($replace as $key => $value) {
            $stub = str_replace($key, $value, $stub);
        }

        return $stub;
    }
}
