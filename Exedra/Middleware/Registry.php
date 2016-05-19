<?php
namespace Exedra\Middleware;

class Registry
{
	/**
	 * List of key-pair middleware registry
	 * @var array registry
	 */
	protected $registry = array();

	/**
	 * Collection of middlewares
	 * @var \Exedra\Middleware\Collection middlewares
	 */
	protected $middlewares;

	public function __construct(Collection $middlewares)
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
	 * @param \Exedra\Runtime\Exec exe
	 * @param Collection middlewares
	 * @param \Closure handle
	 * @return \Closure
	 */
	public function resolve(\Exedra\Runtime\Exec $exe, Collection $middlewares)
	{
		if($middlewares->count() == 0)
			return $handle;

		$middlewares->rewind();

		foreach($middlewares as $middleware)
		{
			if(is_string($middleware) && isset($this->registry[$middleware]))
				$middleware = $this->registry[$middleware];

			$method = 'resolveByType'.ucfirst(strtolower(gettype($middleware)));

			$middlewares[$middlewares->key()] = $this->$method($exe, $middleware);
		}
	}

	/**
	 * Resolve given pattern of string
	 * @param \Exedra\Runtime\Exec exe
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
	 * @param \Exedra\Runtime\Exec exe
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
	 * Get collection of middlewares
	 * @return \Exedra\Middleware\Collection
	 */
	public function getCollection()
	{
		return $this->middlewares;
	}
}


?>