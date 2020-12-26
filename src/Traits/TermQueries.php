<?php

namespace ThaLuffy\Elastic\Traits;

trait TermQueries
{
	public function term($field, $value)
	{
		$this->query[] = [
			'term' => [
				$field => $value,
			],
		];

		return $this;
	}

	public function terms($field, $values)
	{
		$this->query[] = [
			'terms' => [
				$field => $values,
			],
		];

		return $this;
	}
}