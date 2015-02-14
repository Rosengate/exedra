<?php
namespace Exedra\Application\Execution;

/**
 * This class handles the execution's built in or custom pattern(s)
 */

class Pattern
{
	/**
	 * Registry of execution bindable in application layer.
	 * @var array registry
	 */
	protected $registry = array();

	public function __construct(\Exedra\Application\Application $app)
	{
		$this->app = $app;

		$this->register('controllerBuilder', 
			function($value){ return $this->conditionControllerBuilder($value); }, 
			function($value) { return $this->buildController($value); });
	}

	/**
	 * Resolve pattern
	 * @param mixed value
	 */
	public function resolve($value)
	{
		if(is_callable($value))
			return $value;

		foreach($this->registry as $key=>$array)
		{
			if($array['condition'] == true)
				return $array['resolve']($value);
		}

		return $this->app->create('No executional pattern matched.');
	}

	/**
	 * Register a new pattern
	 * @param string key
	 * @param mixed condition
	 * @param mixed resolution
	 */
	public function register($key, $condition, $resolution)
	{
		$this->registry[$key] = array(
			'condition'=> $condition,
			'resolve'=> $resolution
			);
	}

	/**
	 * Checker for controller builder pattern
	 */
	protected function conditionControllerBuilder($value)
	{
		if(strpos($execution, "controller=") === 0)
			return true;

		return false;
	}

	/**
	 * Create a controller builder handler.
	 * @return closure
	 */
	protected function buildController($value)
	{
		return function($exe) use($value)
		{
			$controllerAction	= str_replace("controller=", "", $value);

			list($cname, $action)	= explode("@", $controllerAction);

			$parameter	= Array();

			foreach($exe->params as $key=>$val)
			{
				if(is_array($val))
				{
					$parameter 	= $val;
					$val	= array_shift($parameter);

					$action	= str_replace('{'.$key.'}', $val, $action);
				}
				else
				{
					$cname	= str_replace('{'.$key.'}', $val, $cname);
					$action	= str_replace('{'.$key.'}', $val, $action);
				}

			}

			## execution
			return $exe->controller->execute(Array($cname,Array($exe)),$action,$parameter);
		};

		return $handler;
	}
}

?>