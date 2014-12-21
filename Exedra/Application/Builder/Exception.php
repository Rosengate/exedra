<?php
namespace Exedra\Application\Builder;

class Exception
{
	public function __construct($exe = null)
	{
		$this->exe = $exe;
	}

	## Build an exception.
	public function create($message)
	{
		if($this->exe)
		{
			$route = $this->exe->getRoute(true);
			$params	= $this->exe->params;
		}
		else
		{
			$route = null;
			$params = null;
		}

		throw new \Exedra\Application\Exception\Exception($message, $route, $params);		
	}
}