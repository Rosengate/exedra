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

	public function setExecution($execution)
	{
		$this->execution	= $execution;
	}

	/*public function start($param = null)
	{
		$this->started	= true;

		if($param['onURI'])
			$this->map->setBaseURI($param['onURI']);
	}*/

	public function goToRoute($routeName,$parameter = null)
	{
		$result	= $this->map->executeRoute($routeName,$parameter);
		return $this->executor->execute($result['result']['execute'],$result['parameter'],$this);
	}

	public function build($execution)
	{
		## execute execution first.
		$execution($this);

		## dispatch routing beast.
		// $dispatchResult	= $this->map->dispatch($this->request);


		// return $this->executor->execute($dispatchResult['result']['execute'],$dispatchResult['parameter'],$this);
	}

	public function setExecutionFailRoute($routename)
	{
		$this->executionFailRoute	= $routename;
	}

	public function execute($routename,$parameter = Array())
	{
		try
		{
			$query	= !is_array($routename)?Array("route"=>$routename):$routename;
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
			foreach($route as $routeName=>$routeData)
			{
				if(isset($this->map->binds[$routeName]))
				{
					foreach($this->map->binds[$routeName] as $bindName=>$callback)
					{
						$binds[$bindName][]	= $callback;
					}
				}
			}

			$executor	= new Execution\Executor($this->controller,new Execution\Binder($binds),$this);
			return $executor->execute($route[$routename]['execute'],new Execution\Result($parameter),$this);

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

	/*public function execute()
	{
		## execute execution first.
		$execution	= $this->execution;
		$execution($this);

		## dispatch routing beast.
		$dispatchResult	= $this->map->dispatch($this->request);
		return $this->executor->execute($dispatchResult['result']['execute'],$dispatchResult['parameter'],$this);
	}*/
}
?>