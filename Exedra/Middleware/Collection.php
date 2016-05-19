<?php
namespace Exedra\Middleware;

/**
 * A collection of middleware
 */
class Collection extends \ArrayIterator
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
}