<?php

namespace ThaLuffy\Elastic\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Jenssegers\Mongodb\Eloquent\Model as MongoModel;

class IndexingLog extends MongoModel
{
    use HasFactory;

    protected $fillable = [
        'status',
        'type',
        'index',
        'document_id',
    ];
}
