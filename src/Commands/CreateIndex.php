<?php

namespace ThaLuffy\Elastic\Commands;

use Illuminate\Console\Command;

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
            $index = $this->__getIndex();
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

    private function __getIndex()
    {
        $indices     = config('es.indices');
        $index_name  = $this->argument('index');
        $index       = NULL;

        foreach ($indices as $indexPath) {
            if ($index_name == class_basename($indexPath)) {
                $index = new $indexPath();
                break;
            }
        }

        if (!$index) {
            foreach ($indices as $indexPath) {
                $index = new $indexPath();

                if ($index->getIndexName() == $index_name)
                    break;
                else
                    $index = NULL;
            }
        }

        if (!$index) {
            throw new \Exception('Index not found');
        }

        return $index;
    }
}
