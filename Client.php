<?php

namespace App\Libs\ES;

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
        return 'es.client';
    }
}