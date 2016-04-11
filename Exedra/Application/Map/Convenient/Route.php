<?php
namespace Exedra\Application\Map\Convenient;

class Route extends \Exedra\Application\Map\Route
{
	/**
	 * Alias to setExecute
	 * @param mixed handler
	 */
	public function execute($handler)
	{
		return $this->setExecute($handler);
	}

	/**
	 * Alias to setMiddleware
	 * @param mixed middleware handler
	 */
	public function middleware($middleware)
	{
		return $this->setMiddleware($middleware);
	}

	/**
	 * Alias to setSubroutes
	 * @param \Closure callback
	 */
	public function group($callback)
	{
		return $this->setSubroutes($callback);
	}
}