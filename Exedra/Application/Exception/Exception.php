<?php
namespace Exedra\Application\Exception;

/**
 * Simple Exception class, an \Exception extended
 */

class Exception extends \Exception
{
	private $route;

	public function __construct($msg, $route, $params)
	{
		$this->route = $route;
		$this->params = $params;
		parent::__construct($msg);
	}

	public function getParams()
	{
		return $this->params;
	}

	public function getRoute()
	{
		return $this->route;
	}
}