<?php
namespace Exedra\Application\Middleware;

class Handlers
{
	protected $registry;

	/**
	 * List of instantiated handlers
	 */
	protected $handlers = array();

	/**
	 * Order of handler
	 */
	protected $resolveOrder = array();

	protected $manualResolveOrder = array();

	public function __construct()
	{
		$this->registerDefaultHandlers();
	}

	/**
	 * Overwrite existing resolve orders
	 * @param array orders
	 */
	public function setResolveOrder(array $orders)
	{
		$this->manualResolveOrder = $orders;
	}

	protected function registerDefaultHandlers()
	{
		$this->registry['closure'] = '\Exedra\Application\Middleware\Handler\Closure';
		$this->registry['loader'] = '\Exedra\Application\Middleware\Handler\Loader';
		$this->registry['className'] = '\Exedra\Application\Middleware\Handler\ClassName';

		// closure as being the first.
		$this->resolveOrder[] = 'closure';
		$this->resolveOrder[] = 'loader';
	}

	/**
	 * Register your own handler
	 * @param string name
	 * @param string class
	 */
	public function register($name, $class)
	{
		$this->registry[$name] = $class;
		$this->resolveOrder[] = $name;

		return $this;
	}

	/**
	 * Alias to register
	 */
	public function add($name, $class)
	{
		return $this->register($name, $class);
	}

	/**
	 * Resolve the given middleware pattern
	 * @return \Closure
	 */
	public function resolve($exe, $pattern)
	{
		// if dev preferred their own usage orders
		if(count($this->manualResolveOrder) > 0)
		{
			$resolveOrder = $this->manualResolveOrder;
		}
		// else, use default
		else
		{
			$resolveOrder = $this->resolveOrder;

			// put className on the last order.
			$resolveOrder[] = 'className';
		}

		foreach($resolveOrder as $name)
		{
			$handler = new $this->registry[$name]($name, $exe);

			if($handler->validate($pattern))
			{
				return $handler->resolve($pattern);
			}
		}

		return $exe->exception->create('Unable to find handler for the given middleware pattern');
	}
}