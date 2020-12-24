<?php

namespace App\Libs\ES\Commands;

use Illuminate\Console\Command;

use App\Libs\ES\IndexingLog;

use App\Libs\Helpers\Monitoring;

class IndexRecords extends Command
{
    protected $currentTotalCount;
    protected $currentTotalDuration;
    protected $currentRecordsIndexed;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elastic:index {index} {--limit=} {--from=} {--monitor} {--dump-errors}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Insert records into indexing machine';
    
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
        ini_set('memory_limit','1G');
        
        if (app()->runningInConsole()) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, [$this, 'handleCancel']);
            pcntl_signal(SIGTERM, [$this, 'handleCancel']);
        }

        $isMonitoring   = $this->option('monitor');
        $from           = $this->option('from')  ? intval($this->option('from'))  : 0;
        $limit          = $this->option('limit') ? intval($this->option('limit')) : null;
        $index          = $this->__getIndex();
        $monitor        = new Monitoring();

        foreach ($index->getLinkedModels() as $model) {
            $recordsIndexed = 0;
            $model = new $model();
            $modelName = class_basename($model);

            $this->line("");
            $this->info("Indexing $modelName model");

            $this->info("Getting total number of records...");
            $this->currentTotalCount     = $model->count();
            $this->currentTotalDuration  = 0;
            $this->currentRecordsIndexed = 0;

            $this->info("Total number of records: {$this->currentTotalCount}");

            do 
            {
                $monitor->clearTimers();
                $monitor->startTimer('duration');

                $isMonitoring && $monitor->startTimer('getRecords');
                [$records, $meta]   = $model->getIndexRecords($from, $limit);
                $count              = count($records);
                $isMonitoring && $monitor->endTimer('getRecords');
                
                $actionCounts       = [
                    'created' => 0,
                    'updated' => 0,
                    'deleted' => 0,
                    'skipped' => 0,
                ];
                $params             = [
                    'index'  => [],
                    'create' => [],
                    'update' => [],
                    'delete' => []
                ];

                if (!$count) break;

                $isMonitoring && $monitor->startTimer('createInsertData');
                foreach ($records as $record) {
                    $indexData = $record->sendIndexData($meta, $params);

                    foreach ($indexData as $type => $values) {
                        $params[$type] = array_merge($params[$type], $values);
                    }
                };
                $isMonitoring && $monitor->endTimer('createInsertData');

                $from = $records->last()->{ $model->getKeyName() };

                unset($records);
                unset($meta);
                
                if (!empty($params)) {
                    $isMonitoring && $monitor->startTimer('indexing');
                    $response = $index->bulk($params);
                    $isMonitoring && $monitor->endTimer('indexing');
                    
                    unset($params);
                    
                    // ADD LOGGING
                    if ($response['errors']) {
                        if ($this->option('dump-errors', false)) 
                            dd($response['items']);

                        $errorBatch = collect([]);
                        
                        foreach ($response['items'] as $i => $actionValues) {
                            foreach ($actionValues as $value) {
                                if (isset($value['error']))
                                    $errorBatch->push($value);
                            }
                        }
                        
                        if ($errorBatch->count())
                            IndexingLog::insert($errorBatch->map(fn ($value) => [
                                    'document_id' => $value['_id'],
                                    'status'      => $value['status'],
                                    'index'       => $value['_index'],
                                    'type'        => $value['error']['type'],
                                    'reason'      => $value['error']['reason'],
                                ]) ->toArray());
                    }
                    

                    $response = collect($response['items'])->each(function ($value) use (&$actionCounts, $response) {
                        $action = array_key_first($value);

                        if (isset($value[$action]['error'])) return;

                        switch ($action) {
                            case 'index':
                                if     ($value['index']['result'] === 'created') $actionCounts['created']++;
                                elseif ($value['index']['result'] === 'updated') $actionCounts['updated']++;
                                break;

                            case 'create': 
                                $actionCounts['created']++;
                                break;

                            case 'create': 
                                $actionCounts['created']++;
                                break;

                            case 'update':
                                if     ($value['update']['result'] === 'noop')    $actionCounts['skipped']++;
                                elseif ($value['update']['result'] === 'updated') $actionCounts['updated']++;
                                break;
                        }
                    });

                    unset($response);
                }

                
                $monitor->endTimer('duration');
                $duration = $monitor->getData('duration');
                
                $this->currentRecordsIndexed += $count;
                $this->currentTotalDuration  += $duration;

                $actionCountString = collect($actionCounts)->map(function ($value, $key) {
                    if ($value) return "$key: $value";
                    else return null;
                })->reject(fn ($value) => !$value)->implode(', ');

                $this->comment("$modelName: $count records indexed in {$duration}s (total: {$this->currentRecordsIndexed}, $actionCountString, last ID: $from)");

                if ($isMonitoring) {
                    $this->line('<fg=white>' . collect($monitor->getOutput())->map(fn ($v, $k) => "$k: {$v}s")->implode(', ') . '</>');
                }
            }
            while ($count);
        }

        $this->line("");
        $this->info(ucfirst($index->getIndexName()) . ": {$this->currentRecordsIndexed} records indexed in {$this->currentTotalDuration}s");
        $this->line("");
    }

    public function handleCancel()
    {
        $multiplier = $this->currentTotalCount / $this->currentRecordsIndexed;
        $eta        = $this->currentTotalDuration * $multiplier;
        $seconds    = $eta % 60;
        $minutes    = ($eta / 60) % 60;
        $hours      = floor($eta / 3600);

        $this->info("Estimated duration for all records: $hours:$minutes:$seconds");

        dd("Command cancelled");
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
