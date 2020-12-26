<?php

namespace ThaLuffy\Elastic;

use Elasticsearch\ConnectionPool\SniffingConnectionPool;

use ThaLuffy\Elastic\Base\BaseIndex;
use ThaLuffy\Elastic\Client;

use Exception;
use Closure;

class Builder
{
	protected $index;

	protected $must		= [];

	protected $filter	= [];

	protected $should	= [];

	protected $mustNot	= [];

	protected $size = 10;

	protected $from;

	protected $sort = [];

	protected $source;

	protected $withTotal = false;

	protected $withSort = false;

	protected $searchAfter;

	protected $aggs;

	/**
	 * Create a new instance for the Query builder
	 * 
	 * @param BaseIndex $index
	 * 
	 * @return void
	 */
	public function __construct(BaseIndex $index)
	{
		$this->index = $index;
	}

	/**
	 * Creates the index
	 * 
	 * @return mixed
	 */
    public function create() {
    	$params = [
		    'index' => $this->index->getIndexName(),
		    'body'  => [
                'aliases'  => $this->index->getAliases(),
                'settings' => $this->index->getSettings(),
                'mappings' => $this->index->getMappings(),
            ]
		];
		
		$response = Client::indices()->create($params);

		return $response;
	}

	/**
	 *  Returns true if the the index already exists
	 * 
	 * @return bool
	 */
	public function exists() : bool
	{
		$params = [
		    'index' => $this->index->getIndexName(),
		];

		$response = Client::indices()->exists($params);

		return $response;
	}

	/**
	 * Deletes the index
	 * 
	 * @return mixed
	 */
    public function delete()
    {
    	$params = [
		    'index' => $this->index->getIndexName(),
		];

		$response = Client::indices()->delete($params);

		return $response;
	}
	
	/**
	 * Update a document by id
	 * 
	 * @return mixed
	 */
	public function update($id, $values)
	{
		$params = [
			'index' => $this->index->getIndexName(),
			'id'	=> $id,
			'body'  => [
				'doc' => $values
			]
		];

		$response = Client::update($params);

		return $response;
	}

	public function insert($id = NULL, $params)
	{
    	$params = [
		    'index' => $this->index->getIndexName(),
		    'body'  => $params
		];

		if (!empty($id)) {
			$params['id'] = $id;
		}

		$response = Client::index($params);

		return $response;
    }

    public function bulk($bulkData, $refresh = TRUE)
    {
		foreach ($bulkData as $type => $documents) {
			foreach ($documents as $doc) 
			{
				$meta = [ '_index' => $this->index->getIndexName() ];
				
				if (isset($doc['id']))
				{
					$meta['_id'] = $doc['id'];
				}

				switch ($type) {
					case 'index':
						$params['body'][] = [ 'index' => $meta ];
						$params['body'][] = $doc;
						break;

					case 'create':
						$params['body'][] = [ 'create' => $meta ];
						$params['body'][] = $doc;
						break;

					case 'update':
						$params['body'][] = [ 'update' => $meta ];
						$params['body'][] = [ 'doc' => $doc ];
						break;

					case 'delete':
						$params['body'][] = [ 'delete' => $meta ];
				}
			}
		}

		$params['refresh'] = $refresh;

		$response = Client::bulk($params);
		
		return $response;
    }

    public function query($body)
    {
    	$params = [
    		'index' => $this->index->getIndexName(),
			'body' 	=> $body,
            'track_total_hits' => true
		];
		
		$response = Client::search($params);

		return $response;
	}

    public function find($id)
    {
        $results = $this->query([
            'query' => [
                'ids' => [
                    'values' => [ $id ]
                ]
            ]
		]);

        if (!$results['hits']['total']['value'] || null)
			throw new \Exception("No results for id $id");

        return $results['hits']['hits'][0]['_source'];
	}

	public function must($value)
	{
		$this->must = array_merge($this->must, $value);

		return $this;
	}

	public function filter($value)
	{
		$this->filter = array_merge($this->filter, $value);

		return $this;
	}

	public function should($value)
	{
		$this->should = array_merge($this->should, $value);

		return $this;
	}

	public function mustNot($value)
	{
		$this->mustNot = array_merge($this->mustNot, $value);

		return $this;
	}

	public function isNull($field)
	{
		$this->mustNot[] = [
			'exists' => [
				'field' => $field
			]
		];

		return $this;
	}
	
	public function getBody($json = true)
	{
		return $json ? json_encode($this->__createBody(), JSON_PRETTY_PRINT) : $this->__createBody();
	}

	public function get()
	{
		$results = $this->query($this->__createBody());

		return $this->returnResults($results);
	}

	public function all()
	{
		$results = $this->query([
			'query' => [
                'match_all' => (object) [],
            ],
		]);

		return $this->returnResults($results);
	}

	public function getRaw()
	{
		return $this->query($this->__createBody());
	}

	/**
     * Sets the number of results return by the query.
     *
     * @return $this
     */
    public function size(int $value)
    {
		$this->size = $value;

		return $this;
	}
	
	public function from(int $value)
    {
		$this->from = $value;

		return $this;
    }

	/**
     * Execute the query and get the first result.
     *
     * @return Collection
     */
    public function first()
    {
		$this->size = 1;

        return $this->get()->first();
	}
	
	public function include($fields)
	{
		$this->source['includes'] = $fields;

		return $this;
	}

	public function exclude($fields)
	{
		$this->source['excludes'] = $fields;

		return $this;
	}

	public function withTotal(bool $value = true)
	{
		$this->withTotal = $value;

		return $this;
	}

	public function withSort(bool $value = true)
	{
		$this->withSort = $value;

		return $this;
	}

	public function searchAfter(?array $fields)
	{
		$this->searchAfter = $fields;

		return $this;
	}

	public function sort(string $field, string $order = 'asc')
	{
		$this->sort = is_array($field) ? array_merge($field, $this->sort) : [[ $field => $order ]];

		return $this;
	}

	public function paginate(int $size = 10, int $page = 1)
	{
		$limit 			 = 10000;
		$from  			 = ($page - 1) * $size;
		$lastResultIndex = $from + $size;

		if ($lastResultIndex <= $limit) {
			[$total, $results] = $this->size($size)->from($from)->withTotal()->get();
		}
		else {
			$remainder 		= ($lastResultIndex % $limit) - $size;
			$originalSource = $this->source;
			
			$this->source = null;
			

			for ($i = 1; $i <= intval(($lastResultIndex / $limit)); $i++) {
				if ($remainder < 0 && $i === intval(($lastResultIndex / $limit)))
					$this->searchAfter = $this->exclude(['*'])->size($limit + $remainder)->getRaw()['hits']['hits'][$limit + $remainder - 1]['sort'];
				else
					$this->searchAfter = $this->exclude(['*'])->size($limit)->getRaw()['hits']['hits'][$limit - 1]['sort'];
			}

			if ($remainder > 0)
				$this->searchAfter = $this->exclude(['*'])->size($remainder)->getRaw()['hits']['hits'][$remainder - 1]['sort'];
			
			$this->source = $originalSource;

			[$total, $results] = $this->size($size)->withTotal()->get();
		}

		$totalPages  = ceil($total['value'] / $size);
        
        return [
			'results'	 => $results,
			'pagination' => [
				'size'          => $size,
				'current_page'  => $page,
				'page_results'  => count($results),
				'total_pages'   => $totalPages,
				'total_results' => $total['value']
			]
		];
	}

	public function returnResults($results) 
	{
		$collection = collect($results['hits']['hits'])
			->map(fn ($result) => collect(array_merge(
				[ 'id' => $result['_id'] ],
				$result['_source'],
			)));

		if ($this->withTotal) return [$results['hits']['total'], $collection];

		return $collection;
	}

	public function aggregate($aggregation)
	{
		$this->aggs = $aggregation;

		return $this;
	}

	private function __createBody() : array
	{
		$body = [
			'size'  => $this->size,
		];

		if (!isset($this->wheres['must']) && !isset($this->wheres['must_not']))
			$body['query']['bool']['must'] = [ 'match_all' => new \stdClass ]; 

		$body['query']['bool'] = array_merge($body['query']['bool'] ?? [], $this->wheres ?? []);			

		$this->filters && ($body['query']['filter'] = $this->filters);

		$this->from && ($body['from'] = $this->from);

		$this->source && ($body['_source'] = $this->source);

		!empty($this->sort) && ($body['sort'] = $this->sort);

		$this->searchAfter && ($body['search_after'] = $this->searchAfter);

		$this->aggs && ($body['aggs'] = $this->aggs);

		return $body;
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
        return $this->forwardCallTo(new EloquentBuilder($this), $method, $parameters);
    }
}