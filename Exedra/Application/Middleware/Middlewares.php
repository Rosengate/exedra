<?php
namespace Exedra\Application\Middleware;

/**
 * A collection of middleware
 */
class Middlewares extends \ArrayIterator
{
	/**
	 * Append a middleware to the collection.
	 * Main gateway to stacking middleware
	 * @param mixed middleware
	 */
	public function add($middleware)
	{
		$this->append($middleware);
	}

	/**
	 * Add middleware by array.
	 * @param array middlewares
	 */
	public function addByArray(array $middlewares)
	{
		foreach($middlewares as $middleware)
			$this->add($middleware);
	}
}