<?php

namespace ThaLuffy\Elastic\Base;

use Illuminate\Support\Traits\ForwardsCalls;
use Str;

use ThaLuffy\Elastic\Builder;

class BaseIndex {
    use ForwardsCalls;

    protected $indexName;

    protected $mappings       = [];

    protected $settings       = [];

    protected $aliases        = [];

    protected $linkedModels   = [];

    protected $hosts          = [];

    protected $dynamicMapping = true;

    /* Returns the snakecased classname when $indexName isn't provided */
    public function getIndexName() {
        return $indexName ?? Str::snake(class_basename($this));
    }

    public function getMappings() {
        $mappings = $this->mappings;
        
        return [
            'dynamic'    => $this->dynamicMapping,
            'properties' => $mappings
        ];
    }

    public function getLinkedModels() {
        return $this->linkedModels;
    }

    public function getSettings() {
        $settings = $this->settings;

        return !empty($settings)
            ? $settings
            : new \stdClass();
    }

    public function getAliases() {
        return !empty($this->aliases)
            ? $this->aliases
            : new \stdClass();
    }

    /**
     * Returns a new builder instance
     * 
     * @return Builder
     */
    public function newBuilder() : Builder
    {
        return new Builder($this);
    }

    /**
     * Handle dynamic static method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
	}
    
    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
	public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->newBuilder(), $method, $parameters);
    }
}