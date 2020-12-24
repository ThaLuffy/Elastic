<?php

namespace App\Libs\ES\Base;

use App\Libs\ES\Traits\Indexable;
use App\Libs\ES\Builder;

class IndexTemplate extends Builder
{
    use Indexable;

    protected $mappings     = [];

    protected $linkedModels = [];

    // protected $settings     = [];

    // protected $aliases      = [];

    // protected $hosts        = [];


}