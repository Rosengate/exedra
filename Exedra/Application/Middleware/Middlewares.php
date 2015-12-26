<?php
namespace Exedra\Application\Middleware;

/**
 * A collection of middleware
 */
class Middlewares extends \ArrayIterator
{
	/**
	 * Registry information
	 * Everything including handler
	 */
	protected $registry;

	public function __construct(\Exedra\Application\Middleware\Registry $registry, array $middlewares = array())
	{
		$this->registry = $registry;
		parent::__construct($middlewares);
	}

	/**
	 * Append a middleware to the collection.
	 * Main gateway to stacking middleware
	 * @param mixed middleware
	 */
	public function add($middleware)
	{
		// do a key lookup against registry.
		if(is_string($middleware) && $lookup = $this->registry->lookUp($middleware))
			$middleware = $lookup;

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
	 */
	public function resolve(\Exedra\Application\Execution\Exec $exe)
	{
		$this->rewind();

		while($this->valid())
		{
			$middleware = $this->current();

			$this[$this->key()] = $this->registry->handlers->resolve($exe, $middleware);

			$this->next();
		}
	}
}