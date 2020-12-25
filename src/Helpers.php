<?php

namespace ThaLuffy\Elastic;

use ThaLuffy\Elastic\Models\IndexLog;

class Helpers
{
    public static function getIndexLogModel()
    {
        $model = IndexLog::class;

        if ($customModel = config('elastic.custom_log_model'))
            $model = $customModel;

        return $model;
    }

    public static function getIndexByName($indexName)
    {
        $indicesFolder = config('elastic.indices_folder');
        $indices       = config('elastic.indices');

        foreach ($indices as $indexPath) {
            if ($index_name == class_basename($indexPath)) {
                return new $indexPath();
            }
        }

        foreach ($indices as $indexPath) {
            $index = new $indexPath();

            if ($index->getIndexName() == $indexName)
                return $index;
        }

        throw new \Exception('Index not found');
    }
}