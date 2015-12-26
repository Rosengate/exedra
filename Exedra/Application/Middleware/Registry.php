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

	public function __construct(\Exedra\Application\Application $app)
	{
		$this->app = $app;

		$this->middlewares = new \Exedra\Application\Middleware\Middlewares($this);

		$this->handlers = new Handlers;
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
		$middlewares = new \Exedra\Application\Middleware\Middlewares($this, $this->middlewares->getArrayCopy());

		return $middlewares;
	}
}


?>