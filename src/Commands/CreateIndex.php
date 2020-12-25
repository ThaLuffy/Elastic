<?php

namespace ThaLuffy\Elastic\Commands;

use Illuminate\Console\Command;

use ThaLuffy\Elastic\Helpers;

class CreateIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elastic:create {index} {--recreate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create ES index';

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
     * @return mixed
     */
    public function handle()
    {
        try
        {
            $index = Helpers::getIndexByName($this->argument('index'));
            $index->createIndex($this->option('recreate'));

            $this->line("\n");
            $this->info("Successfully created index '{$index->getIndexName()}' 🚀.");
            $this->line("\n");
        }
        catch (\Exception $e)
        {
            $this->line("\n");
            $this->error($e->getMessage());
            $this->line("\n");
        }
    }
}
