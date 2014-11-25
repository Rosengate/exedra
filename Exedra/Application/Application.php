<?php
namespace Exedra\Application;

class Application
{
	private $started			= false;
	private $name				= null;
	private $executor			= null;
	public $router				= null;
	public $request				= null;
	public $response			= null;
	public $structure			= null;
	private $executionFailRoute	= null;

	public function __construct($name,$dependencies)
	{
		$this->name			= $name;
		$this->map			= $dependencies['map'];
		$this->request		= $dependencies['request'];
		$this->structure	= $dependencies['structure'];
		$this->controller	= $dependencies['controller'];
		$this->layout		= $dependencies['layout'];
		$this->response		= $dependencies['response'];
	}

	public function setExecutionFailRoute($routename)
	{
		$this->executionFailRoute	= $routename;
	}

	/*
	main application execution interface.
	*/
	public function execute($query,$parameter = Array())
	{
		try
		{
			$query	= !is_array($query)?Array("route"=>$query):$query;
			$result	= $this->map->find($query);

			if(!$result)
			{
				$q	= Array();
				foreach($query as $k=>$v) $q[]	= $k." : ".$v;
				throw new \Exception("Route not found. Query :<br>".implode("<br>",$q), 1);
			}

			$route		= $result['route'];
			$routename	= $result['name'];
			$parameter	= array_merge($result['parameters'],$parameter);

			$binds		= Array();
			$configs	= Array();
			foreach($route as $routeName=>$routeData)
			{
				## Binds
				if(isset($this->map->binds[$routeName]))
				{
					foreach($this->map->binds[$routeName] as $bindName=>$callback)
					{
						$binds[$bindName][]	= $callback;
					}
				}

				## Configs
				if(isset($this->map->config[$routeName]))
				{
					foreach($this->map->config[$routeName] as $paramName=>$val)
					{
						$configs[$paramName]	= $val;
					}
				}
			}

			## Prepare result parameter.
			$executionResult	= new Execution\Result($parameter);

			if($configs)
				$executionResult->addVariable("config",$configs);

			$executor	= new Execution\Executor($this->controller,new Execution\Binder($binds),$this);
			return $executor->execute($route[$routename]['execute'],$executionResult,$this);

		} 
		catch(\Exception $e)
		{
			if($this->executionFailRoute)
			{
				$failRoute = $this->executionFailRoute;

				## set this false, so that it wont loop if later this fail route doesn't exists.
				$this->executionFailRoute = false;
				return $this->execute($failRoute,Array("exception"=>$e));
			}
			else
			{
				return $e->getMessage();
			}
		}
	}
}
?>