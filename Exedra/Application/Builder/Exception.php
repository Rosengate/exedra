<?php
namespace Exedra\Application\Builder;

/**
 * Simple exception builder.
 */

class Exception
{
	public function __construct($exe = null)
	{
		$this->exe = $exe;
	}

	/**
	 * Create exception message.
	 * @param string message
	 */
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

		if($route)
			$message = "[Route : $route] ".$message;

		throw new \Exedra\Application\Exception\Exception($message, $route, $params);		
	}
}