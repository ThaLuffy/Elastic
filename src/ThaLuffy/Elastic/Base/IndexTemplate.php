<?php

namespace ThaLuffy\Elastic\Base;

use ThaLuffy\Elastic\Traits\Indexable;
use ThaLuffy\Elastic\Builder;

class IndexTemplate extends Builder
{
    use Indexable;

    protected $mappings     = [];

    protected $linkedModels = [];

    // protected $settings     = [];

    // protected $aliases      = [];

    // protected $hosts        = [];


}