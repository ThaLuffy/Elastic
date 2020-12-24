<?php

namespace ThaLuffy\Elastic;

use Illuminate\Support\Facades\Facade;

class Client extends Facade
{
    /**
     * Get the facade.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'elastic.client';
    }
}