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
	private $currentRoute		= null;
	private $currentResult		= null;

	public function __construct($name,$dependencies)
	{
		$this->name			= $name;
		$this->exedra		= $dependencies['exedra'];
		$this->map			= $dependencies['map'];
		$this->request		= $dependencies['request'];
		$this->structure	= $dependencies['structure'];
		$this->loader		= $dependencies['loader'];
		$this->controller	= $dependencies['controller'];
		$this->layout		= $dependencies['layout'];
		$this->view			= $dependencies['view'];
		$this->session		= $dependencies['session'];
		$this->model		= $dependencies['model'];
	}

	public function setExecutionFailRoute($routename)
	{
		$this->executionFailRoute	= $routename;
	}

	## return current execution result.
	public function getResult()
	{
		return $this->currentResult;
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

			## save current route result.
			$this->currentRoute	= &$result;	

			$subapp		= null;
			$binds		= Array();
			$configs	= Array();

			foreach($route as $routeName=>$routeData)
			{
				## Sub app
				$subapp	= isset($routeData['subapp'])?$routeData['subapp']:$subapp;

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

			## Prepare result parameter and automatically create controller and view builder.
			$context	= $this;
			$executionResult	= new Execution\Exec($routename,$this,$parameter,function($result) use($context,$subapp)
			{
				## check if has subapp passed through parameter.
				$result->controller	= new Builder\Controller($context->structure,$context->loader,$subapp);
				$result->view		= new Builder\View($context->structure,$context->loader,$subapp);
			});

			## has config.
			if($configs)
				$executionResult->addVariable("config",$configs);

			## give result the container;
			$executionResult->addVariable("container",$container = new Execution\Container(Array("app"=>$this,"exe"=>$executionResult)));

			$this->currentResult	= $executionResult;
			$executor	= new Execution\Executor($this->controller,new Execution\Binder($binds),$this);
			$execution	= $executor->execute($route[$routename]['execute'],$executionResult,$container);

			$this->currentResult	= null;
			return $execution;
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
				$routeName	= $this->currentRoute['name'];
				return "<pre><hr><u>Execution Exception :</u>\nRoute : $routeName\n".$e->getMessage()."<hr>";
			}
		}
	}
}
?>