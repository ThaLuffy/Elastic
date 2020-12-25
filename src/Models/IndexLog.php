<?php

namespace ThaLuffy\Elastic\Models;

use Illuminate\Database\Eloquent\Model;

class IndexLog extends Model
{
    protected $fillable = [
        'status',
        'type',
        'index',
        'model',
        'document_id',
        'job_id',
    ];
}
