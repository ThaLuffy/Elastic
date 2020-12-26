<?php

namespace ThaLuffy\Elastic;

use Illuminate\Support\Traits\ForwardsCalls;

class EloquentBuilder
{
    protected $builder;
    
    protected $filter   = [];

    protected $mustNot  = [];

    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

	public function where($field, $operator = null, $value = null)
	{
        $args = func_get_args();

		if (is_array($field) && is_null($operator)) {
			foreach ($field as $key => $value) {
				if (is_numeric($key) && is_array($value)) {
					$this->where(...array_values($value));
				}
				else {
					$this->where($key, '=', $value);
				}
			}
	
			return $this;
		}

        if (count($args) === 3) [$field, $operator, $value] = $args;
		else {
			$value = $operator;
			$operator = '=';
		}

        switch ($operator) {
			case '=':  return $this->whereIs($field, $value);
			case '>':  return $this->greaterThan($field, $value);
			case '<':  return $this->smallerThan($field, $value);
			case '>=': return $this->greaterEqualThan($field, $value);
			case '<=': return $this->smallerThan($field, $value);
			case '!=':
			case '<>': return $this->whereNot($field, $value);
		}

		return $this;
	}
	
	public function whereIn($field, $values)
	{
		$this->filter[] = [
			'terms' => [
				$field => $values,
			],
		];
		
		return $this;
	}

	public function whereNotIn($field, $value)
	{
		$this->mustNot[] = [
			'terms' => [
				$field => $values,
			],
		];
		
		return $this;
	}

	public function whereIs($field, $value)
	{
		$this->filter[] = [
			'term' => [
				$field => $value,
			],
		];
		
		return $this;
	}

	public function greaterThan($field, $value)
	{
		$this->filter[] = [
			'range' => [
				$field => [
					'gt' => $value,
				],
			],
		];

		return $this;
	}

	public function greaterEqualThan($field, $value)
	{
		$this->filter[] = [
			'range' => [
				$field => [
					'gte' => $value,
				],
			],
		];

		return $this;
	}

	public function smallerThan($field, $value)
	{
		$this->filter[] = [
			'range' => [
				$field => [
					'lt' => $value,
				],
			],
		];

		return $this;
	}

	public function smallerEqualThan($field, $value)
	{
		$this->filter[] = [
			'range' => [
				$field => [
					'lte' => $value,
				],
			],
		];
		
		return $this;
	}

	public function whereNot($field, $value) 
	{
		$this->mustNot[] = [
			'term' => [
				$field => $value,
			],
		];

		return $this;
	}

	public function whereGeoInBoundingbox($field, array $topRight, array $bottomLeft)
	{
		$this->filter = [
			"geo_bounding_box" => [
				$field => [
					'top_right'   => $topRight,
					"bottom_left" => $bottomLeft
				],
			]
		];

		return $this;
	}

	public function whereGeoIn($field, array $coordinates, string $type = "point", string $relation = "contains")
	{
		$this->filter = [
			"geo_shape" => [
				$field => [
					'shape' => [
						"coordinates" => $coordinates,
						"type" => $type
					],
					"relation" => $relation
				],
			]
		];

		return $this;
	}

	/**
     * Add a whereBetween condition.
     *
     * @param  string  $field
     * @param  array  $value
     * @return $this
     */
    public function whereBetween($field, array $value)
    {
        $this->filter[] = [
            'range' => [
                $field => [
                    'gte' => $value[0],
                    'lte' => $value[1],
                ],
            ],
        ];

        return $this;
	}
    
    // /**
    //  * Handle dynamic method calls into the model.
    //  *
    //  * @param  string  $method
    //  * @param  array  $parameters
    //  * @return mixed
    //  */
	// public function __call($method, $parameters)
    // {
    //     return $this->forwardCallTo($this->builder, $method, $parameters);
    // }
}