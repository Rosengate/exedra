<?php
namespace Exedra\Application\Execution;

/**
 * Handle list of registered execution handler
 */
class Handlers
{
	/**
	 * Application instance
	 * @var \Exedra\Application\Application
	 */
	protected $app;

	/**
	 * Registry of execution bindable in application layer.
	 * @var array registry
	 */
	protected $registry = array();

	/**
	 * List of instantiated handlers
	 * @var array handlers
	 */
	protected $handlers = array();

	public function __construct(\Exedra\Application\Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Register execution handler
	 * @param string name
	 * @param string class
	 * @return self
	 */
	public function register($name, $class)
	{
		$this->registry[$name] = $class;

		return $this;
	}

	/**
	 * Alias for register()
	 */
	public function add($name, $class)
	{
		return $this->register($name, $class);
	}

	/**
	 * Resolve a handler
	 * @param \Exedra\Application\Execution\Exec exe
	 * @param mixed pattern
	 * @return \Closure
	 */
	public function resolve(\Exedra\Application\Execution\Exec $exe, $pattern)
	{
		foreach($this->registry as $name => $className)
		{
			$handler = isset($this->handlers[$name]) ? $this->handlers[$name] : $this->handlers[$name] = new $className($name, $exe);

			if($handler->validate($pattern) === true)
			{
				$resolve = $handler->prepare($pattern);

				if(!(is_callable($resolve)))
					return $exe->exception->create('The resolve() method for handler \''.$name.'\' must return \Closure or callable');

				return $resolve;
			}
		}

		return $exe->exception->create('No executional pattern handler matched.'.(is_string($pattern) ? ' Pattern : '.$pattern : ''));
	}
}

?>