<?php

namespace App\Libs\ES\Traits;

trait Indexable {
    public static function boot()
    {
        parent::boot();

        self::updated(function ($model) {
            $model->index && $model->updateIndex();
        });
    }

    public function updateIndex()
    {
        if (!$this->index) throw new \Exception("Protected value 'index' missing from model.");

        $index = $this->index;

        $index::update($this->id, $this->toIndexArray(null));
    }

    public function getBulkSize()
    {
        return $this->bulkSize ?? config('es.default.bulkSize');
    }

    public function getIndexRecords($from = 0, $bulkSize): array
    {
        return [
            self::when($from, function($q) use ($from) { return $q->where($this->getKeyName(), '>', $from); })
                ->limit($bulkSize ?? $this->getBulkSize())->orderBy($this->getKeyName(), 'asc')->get()
        , null];
    }

    public function toIndexArray($meta)
    {
        return $this->toArray();
    }

    public function sendIndexData($meta = [], &$currentParams)
    {
        $document = $this->toIndexArray($meta);

        return [
            'index'         => [ $document ],
            'create'        => [],
            'update'        => [],
            'delete'        => [],
        ];
    }
}