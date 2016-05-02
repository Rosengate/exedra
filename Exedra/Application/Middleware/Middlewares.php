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
	 * @var \Exedra\Application\Middleware\Registry
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
	 * @param \Closure handle - first handle
	 */
	public function resolve(\Exedra\Application\Execution\Exec $exe, \Closure $handle)
	{
		if($this->count() == 0)
			return $handle;

		$this->rewind();

		while($this->valid())
		{
			$this[$this->key()] = $this->registry->resolveMiddleware($exe, $this->current());

			$this->next();
		}

		// set the given execution handler on the last
		$this[$this->count()] = $handle;

		$this->rewind();

		return $this->current();
	}
}