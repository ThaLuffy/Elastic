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
            
            if ($index->exists())
                $this->recreateIndexOption($index);

            $this->line("\n");
            $this->info("Successfully created index '{$index->getIndexName()}' 🚀.");
            $this->line("\n");
        }
        catch (\Throwable $th)
        {
            $this->line("\n");
            $this->error($th->getMessage());
            $this->line("\n");
        }
    }

    private function recreateIndexOption($index)
    {
        if ($this->option('recreate') || $this->confirm('This index already exists. Do you want to recreate it?'))
            return $this->index->delete();

        throw new \Exception("Command cancelled");
    }
}
