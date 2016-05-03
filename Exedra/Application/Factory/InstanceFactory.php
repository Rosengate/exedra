<?php
namespace Exedra\Application\Factory;

abstract class InstanceFactory
{
	/**
	 * Base namespace
	 * @var string
	 */
	protected $baseNamespace;

	/**
	 * Namespace name.
	 * @var string
	 */
	protected $namespace;

	/**
	 * Application instance
	 * @var \Exedra\Application
	 */
	protected $app;

	public function __construct($baseNamespace, $module = null)
	{
		$this->baseNamespace = $baseNamespace;

		$this->module = $module;
	}

	/**
	 * Build class name
	 * @param string class
	 * @return string
	 */
	protected function buildClassName($class, $namespace, $module = null)
	{
		$className = $this->baseNamespace;

		if($module)
			$className .= '\\'.$module;

		$className .= '\\'.$namespace;

		$className .= '\\'.$class;

		return $className;
	}

	/**
	 * Create the instance
	 * @param string|array relative classname|definition
	 * @param array constructorParam
	 * @return Object
	 */
	public function create($definition, array $args = array())
	{
		$className = $definition;

		if(is_array($definition))
		{
			$className = $definition['class'];

			$module = isset($definition['module']) ? $definition['module'] : $this->module;

			if(isset($definition['arguments']))
				$args = array_merge($args, $definition['arguments']);
		}
		else
		{
			$className = $definition;

			$module = $this->module;
		}

		$className = $this->buildClassName($className, $this->namespace, $module);
		
		// class name does not exists in the given path.
		if(!class_exists($className))
			throw new \Exedra\Exception\NotFoundException('Class named ['.$className.'] does not exists.');

		if(count($args))
		{
			$reflection	= new \ReflectionClass($className);

			$instance	= $reflection->newInstanceArgs($args);
		}
		else
		{
			$instance	= new $className;
		}

		return $instance;
	}

	/**
	 * Instantiate and invoke the method
	 * - if definition is string, instantiate based on that string.
	 * - if definition is array, take first element as class name, and second as construct parameters
	 *   - if it has key [class], expect it as class definition.
	 * - else, expect it as the object instance.
	 * @param mixed cname
	 * @param string method
	 * @param array parameter
	 * @return execution
	 */
	public function execute($definition, $method, array $args = array())
	{
		if(is_string($definition))
		{
			$instance	= $this->create($definition);
		}
		else if(is_array($definition))
		{
			if(isset($definition['class']))
				$instance = $this->create($definition);
			else 
				$instance	= $this->create($definition[0], $definition[1]);
		}
		else
		{
			$instance	= $definition;
		}

		if(!method_exists($instance, $method))
		{
			$reflection	= new \ReflectionClass($instance);

			throw new \Exedra\Exception\NotFoundException($reflection->getName()." : Method [$method] does not exists.");
		}

		return call_user_func_array(array($instance, $method), $args);
	}
}