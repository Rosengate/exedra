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

	/**
	 * Resolve middlewares all into usable callbacks.
	 * And save the change
	 * @param \Exedra\Application\Execution\Exec
	 * @param \Closure handle - first handle
	 */
	public function resolve(\Exedra\Application\Execution\Exec $exe, \Exedra\Application\Middleware\Registry $registry, \Closure $handle)
	{
		if($this->count() == 0)
			return $handle;

		$this->rewind();

		while($this->valid())
		{
			$middleware = $this->current();

			if(is_string($middleware) && $lookup = $registry->lookUp($middleware))
				$middleware = $lookup;

			$this[$this->key()] = $registry->resolveMiddleware($exe, $middleware);

			$this->next();
		}

		// set the given execution handler on the last
		$this[$this->count()] = $handle;

		$this->rewind();

		return $this->current();
	}
}