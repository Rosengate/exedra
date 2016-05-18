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
	 * List of global middlewares
	 * @var \Exedra\Application\Middleware\Middlewares middlewares
	 */
	protected $middlewares = array();

	public function __construct(Middlewares $middlewares)
	{
		$this->middlewares = $middlewares;
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
	 * Resolve given collection of middleware
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

			if(is_string($middleware) && isset($this->registry[$middleware]))
				$middleware = $this->registry[$middleware];

			$method = 'resolveByType'.ucfirst(strtolower(gettype($middleware)));

			$middlewares[$middlewares->key()] = $this->$method($exe, $middleware);

			$middlewares->next();
		}

		// set the given execution handler on the last
		$middlewares[$middlewares->count()] = $handle;

		$middlewares->rewind();

		return $middlewares->current();
	}

	/**
	 * Resolve given pattern of string
	 * @param \Exedra\Application\Execution\Exec exe
	 * @param string middleware
	 * @return \Closure
	 */
	protected function resolveByTypeString($exe, $middleware)
	{
		// assume given middleware pattern as class name
		return function() use($middleware)
		{
			$middleware = new $middleware;
			
			return call_user_func_array(array($middleware, 'handle'), func_get_args());
		};
	}

	/**
	 * Resolve given object
	 * @param \Exedra\Application\Execution\Exec exe
	 * @param object middleware
	 *
	 * @throws \Exedra\Exception\InvalidArgumentException
	 */
	protected function resolveByTypeObject($exe, $middleware)
	{
		if($middleware instanceof \Closure)
			return $middleware;

		throw new \Exedra\Exception\InvalidArgumentException('Unable to resolve middleware with type [object].');
	}

	/**
	 * Key based middleware register
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
	public function getMiddlewaresCopy()
	{
		$middlewares = new \Exedra\Application\Middleware\Middlewares($this->middlewares->getArrayCopy());

		return $middlewares;
	}
}


?>