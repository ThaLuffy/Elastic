<?php

namespace ThaLuffy\Elastic\Commands;

use Illuminate\Console\Command;

use ThaLuffy\Elastic\Monitoring;
use ThaLuffy\Elastic\Helpers;

class IndexRecords extends Command
{
    protected $currentAllRecordsCount;
    protected $currentTotalCount;
    protected $currentTotalDuration;
    protected $currentRecordsIndexed;
    protected $jobId;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elastic:index {index} {--limit=} {--from=} {--monitor} {--dump-errors} {--easy-count}';

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
        $easyCount      = $this->option('easy-count');
        $from           = $this->option('from')  ? $this->option('from') : 0;
        $limit          = $this->option('limit') ? intval($this->option('limit')) : null;
        $index          = Helpers::getIndexByName($this->argument('index'));
        $monitor        = new Monitoring();

        foreach ($index->getLinkedModels() as $model) {
            $model = new $model();
            $modelName = class_basename($model);

            $this->line("");
            $this->info("Indexing $modelName model");

            $this->info("Getting total number of records...");
            [$queryBuilder, $meta]        = $model->getIndexQueryBuilder();
            $this->currentAllRecordsCount = $easyCount ? $model->count() : $queryBuilder->count();
            $this->currentTotalCount      = $easyCount ? $model->count() : $queryBuilder->when($from, fn ($q) => $q->where($model->getKeyName(), '>', $from))->count();
            $this->currentTotalDuration   = 0;
            $this->currentRecordsIndexed  = 0;
            $this->jobId                  = (string) \Str::uuid();

            $this->info("Total number of records: {$this->currentTotalCount}");

            do 
            {
                $monitor->clearTimers();
                $monitor->startTimer('duration');

                $isMonitoring && $monitor->startTimer('getRecords');
                [$queryBuilder, $meta] = $model->getIndexQueryBuilder();

                $records = $queryBuilder
                    ->when($from, fn ($q) => $q->where($model->getKeyName(), '>', $from))
                    ->limit($limit ?? $model->getBulkSize())
                    ->orderBy($model->getKeyName(), 'asc')
                    ->get();

                $count = count($records);
                
                $isMonitoring && $monitor->endTimer('getRecords');
                
                $actionCounts       = [
                    'created' => 0,
                    'updated' => 0,
                    'deleted' => 0,
                    'skipped' => 0,
                    'errors'  => 0,
                ];
                $params             = [
                    'index'  => [],
                    'create' => [],
                    'update' => [],
                    'delete' => []
                ];

                if (!$count) break;

                $isMonitoring && $monitor->startTimer('createIndexDocuments');
                foreach ($records as $record) {
                    $indexData = $record->sendIndexData($meta, $params);

                    foreach ($indexData as $type => $values) {
                        $params[$type] = array_merge($params[$type], $values);
                    }
                };
                $isMonitoring && $monitor->endTimer('createIndexDocuments');

                $from = $records->last()->{ $model->getKeyName() };

                unset($records);
                unset($meta);
                
                if (!empty($params)) {
                    $isMonitoring && $monitor->startTimer('indexing');
                    $response = $index->bulk($params);
                    $isMonitoring && $monitor->endTimer('indexing');
                    
                    unset($params);
                    
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
                        
                        $errorCount = $errorBatch->count();

                        if ($errorCount) {
                            Helpers::getIndexLogModel()::insert($errorBatch->map(fn ($value) => [
                                'document_id' => $value['_id'],
                                'status'      => $value['status'],
                                'index'       => $value['_index'],
                                'type'        => $value['error']['type'],
                                'reason'      => $value['error']['reason'],
                            ]) ->toArray());

                            $actionCounts['errors'] += $errorCount;
                        }
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

                $timeRemaningString = $this->__getTimeRemaningString();

                $this->comment("$modelName: $count records indexed in {$duration}s (total: {$this->currentRecordsIndexed}, $actionCountString, last ID: $from, $timeRemaningString)");

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
        $multiplier        = $this->currentAllRecordsCount / $this->currentRecordsIndexed;
        $timeForAllRecords = $this->currentTotalDuration * $multiplier;

        [$tHours, $tMinutes, $tSeconds] = $this->__calcTimeHMS($timeForAllRecords);
        $this->info("Estimated duration for all records: $tHours:$tMinutes:$tSeconds");

        dd("Command cancelled");
    }

    private function __getTimeRemaningString() : string
    {
        if (!$this->currentRecordsIndexed)
            return "Time remaning: calculating...";

        $avgTimePerRecord  = $this->currentTotalDuration / $this->currentRecordsIndexed;
        $remaningRecords   = $this->currentTotalCount - $this->currentRecordsIndexed;
        $timeRemaning      = $avgTimePerRecord * $remaningRecords;

        [$hours, $minutes, $seconds] = $this->__calcTimeHMS($timeRemaning);

        return "Time remaning: $hours:$minutes:$seconds";
    }

    private function __calcTimeHMS($secToCalc) : array
    {
        $seconds    = str_pad($secToCalc % 60, 2, "0", STR_PAD_LEFT);
        $minutes    = str_pad(($secToCalc / 60) % 60, 2, "0", STR_PAD_LEFT);
        $hours      = str_pad(floor($secToCalc / 3600), 2, "0", STR_PAD_LEFT);

        return [$hours, $minutes, $seconds];
    }
}
