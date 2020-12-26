<?php

namespace ThaLuffy\Elastic;

use ThaLuffy\Elastic\Models\IndexLog;
use Symfony\Component\Finder\Finder;

use ReflectionClass;
use Str;

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
        $indexFolders = config('elastic.index_folders');
        $indices      = config('elastic.indices');

        dd($indexFolders, $indices, self::indicesIn(app_path($indexFolders[0])));

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

    public static function indicesIn($directory)
    {
        $namespace = app()->getNamespace();

        $indices = [];

        foreach ((new Finder)->in($directory)->files() as $index) {
            $indices[] = $namespace.str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after($index->getPathname(), app_path().DIRECTORY_SEPARATOR)
            );
        }

        dd($indices);

        static::resources(
            collect($resources)->sort()->all()
        );
    }
}