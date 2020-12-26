<?php

return [
	/*
    |--------------------------------------------------------------------------
    | Indices folder
    |--------------------------------------------------------------------------
    |
    | Register the folder you want to use for your indices. These indices get
    | autoloaded so you won't have register them explicitly.
    |
	*/
    
    'indices_folders' => [
        'Indices'
    ],


    /*
    |--------------------------------------------------------------------------
    | Indices
    |--------------------------------------------------------------------------
    |
    | If you don't want to use folders, you can register your indices here.
    |
	*/
    
    'indices' => [
        // Path/To/Index
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom indexing logs model
    |--------------------------------------------------------------------------
    |
    | Define a custom index log model.
    |
	*/
    
    'custom_log_model' => null,

	/*
    |--------------------------------------------------------------------------
    | Hosts
    |--------------------------------------------------------------------------
    |
    | Define your Elasticsearch hosts here. You can define multiple hosts.
    |
	*/
	
	'hosts' => [
        env('ES_HOST', 'localhost:9200'),
	],

	/*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | Write message here
    |
	*/
	
	'default' => [
		'bulkSize' => 100,
	],
];