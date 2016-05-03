<?php
namespace Exedra\Application\Middleware;

class Registry
{
	/**
	 * List of key-pair middleware registry
	 * @var array registry
	 */
	protected $registry = array();

	/**
	 * Handlers instance
	 * @var \Exedra\Application\Middleware\Handlers
	 */
	public $handlers;

	/**
	 * List of global middlewares
	 * @var \Exedra\Application\Middleware\Middlewares middlewares
	 */
	protected $middlewares = array();

	public function __construct(Middlewares $middlewares, Handlers $handlers)
	{
		$this->middlewares = $middlewares;

		$this->handlers = $handlers;
	}

	/**
	 * Append middleware into middlewares
	 * @param mixed middleware
	 */
	public function add($middleware)
	{
		if(is_array($middleware))
		{
			foreach($middleware as $m)
				$this->middlewares->add($m);
		}
		else
		{
			$this->middlewares->add($middleware);
		}

		return $this;
	}

	/**
	 * @param \Exedra\Application\Execution\Exec exe
	 * @param Middlewares middlewares
	 * @param \Closure handle
	 * @return \Closure
	 */
	public function resolve(\Exedra\Application\Execution\Exec $exe, Middlewares $middlewares, \Closure $handle)
	{
		if($middlewares->count() == 0)
			return $handle;

		$middlewares->rewind();

		while($middlewares->valid())
		{
			$middleware = $middlewares->current();

			if(is_string($middleware) && $lookup = $this->lookUp($middleware))
				$middleware = $lookup;

			$middlewares[$middlewares->key()] = $this->handlers->resolve($exe, $middleware);

			$middlewares->next();
		}

		// set the given execution handler on the last
		$middlewares[$middlewares->count()] = $handle;

		$middlewares->rewind();

		return $middlewares->current();
	}

	/**
	 * Look up named middleware information
	 * @param string key
	 * @return pattern|null
	 */
	public function lookUp($key)
	{
		if(isset($this->registry[$key]))
			return $this->registry[$key];

		return null;
	}

	/**
	 * Key based middleware registrer
	 * @param string key
	 * @param mixed pattern|null
	 * @return self
	 */
	public function register($key, $pattern = null)
	{
		if(is_array($key))
			foreach($key as $k => $value)
				$this->registry[$k] = $value;
		else
			$this->registry[$key] = $pattern;

		return $this;
	}

	/**
	 * Get cloned list of middlewares
	 * @return \Exedra\Application\Middleware\Middlewares
	 */
	public function getMiddlewares()
	{
		$middlewares = new \Exedra\Application\Middleware\Middlewares($this->middlewares->getArrayCopy());

		return $middlewares;
	}
}


?>