<?php

namespace ThaLuffy\Elastic\Builders;

use Illuminate\Support\Traits\ForwardsCalls;

use ThaLuffy\Elastic\Builder;
use ThaLuffy\Elastic\Traits\TermQueries;

use Closure;

class CompoundBuilder
{
    use ForwardsCalls, TermQueries;
    
	protected $must		= [];

	protected $filter	= [];

	protected $should	= [];

	protected $mustNot	= [];
	
    protected $query 	= [];

    protected $builder;

    public function __construct(Builder $builder = null)
    {
		$this->builder = $builder;
    }
    
	public function must($value)
	{
		$this->must = array_merge($this->must, $value);

		return $this;
	}

	public function filter($value)
	{
		$this->filter = $value instanceof Closure
            ? $this->__nestedBuilder($value)
            : array_merge($this->filter, $value);

		return $this;
	}

	public function should($value)
	{
        $this->should = $value instanceof Closure
            ? $this->__nestedBuilder($value)
            : array_merge($this->should, $value);

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

	public function createBoolQuery()
	{
		$query = [];

		!empty($this->must)    && $query['bool']['must']  = $this->must;
		!empty($this->filter)  && $query['bool']['filter'] = $this->filter;
		!empty($this->should)  && $query['bool']['should'] = $this->should;
		!empty($this->mustNot) && $query['bool']['must_not'] = $this->mustNot;

		return $query;
	}

    public function returnQuery()
    {
		$result = $this->query;

		$boolQuery = $this->createBoolQuery();

		if (!empty($boolQuery))
			$result = array_merge($this->query, [ $boolQuery ]);

        return $result;
    }
    
    private function __nestedBuilder($closure)
    {
		$nestedBuilder = $closure(new static);
		
        return $nestedBuilder->returnQuery();
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
        $eloquentBuilder = new EloquentBuilder($this);

        if (in_array($method, get_class_methods($eloquentBuilder)))
            return $this->forwardCallTo($eloquentBuilder, $method, $parameters);

        if (!$this->builder)
            return null;

        $this->builder->setDSLQuery($this->createBoolQuery());

        return $this->forwardCallTo($this->builder, $method, $parameters);
    }
}