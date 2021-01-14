<?php

namespace ThaLuffy\Elastic\Traits;

trait Indexable {
    public static function boot()
    {
        parent::boot();

        self::updated(function ($model) {
            $model->index && $model->updateIndex();
        });
    }

    public function addMetaData($iterationResults)
    {
        return null;
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

    public function getIndexQueryBuilder()
    {
        return self::select('*');
    }

    public function toIndexArray($meta)
    {
        return $this->toArray();
    }

    public function sendIndexData($meta, &$currentParams)
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