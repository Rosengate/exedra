<?php
namespace Exedra\Application\Execution;

class Executor
{
	private $controller	= null;
	
	public function __construct($controller,$binder)
	{
		$this->controller 	= $controller;
		$this->binder		= $binder;
	}

	public function execute($execution,$result)
	{
		if(is_object($execution))
		{
			if($this->binder->hasBind("middleware"))
			{
				$parent_execution			= $this->binder->getBind("middleware");

				$result->containers			= $parent_execution;

				## set the last of the container as execution.
				$result->containers[count($parent_execution)]	= $execution;

				// return $parent_execution($params,$execution);
				$result	= $parent_execution[0]($result);
			}
			else
			{
				$result	= $execution($result);
			}

			return Resolver::resolve($result);
		}

		## look for execution keyword.
		if(is_string($execution))
		{
			## controller.
			if(strpos($execution, "controller=") === 0)
			{
				$controllerAction	= str_replace("controller=", "", $execution);

				$handler	= function($result) use($controllerAction,$result)
				{
					return $this->executeController($controllerAction,$result);
				};

				## recursive to use the main execution.
				return $this->execute($handler,$result);
			}
		}
	}

	private function executeController($controllerAction,$exe)
	{
		list($cname,$action)	= explode("@",$controllerAction);

		if($exe)
		{
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
		}

		if($this->binder->hasBind("controller.execute"))
		{
			$controller_execute	= $this->binder->getBind("controller_execute");
			return $controller_execute($cname,$action);
		}

		## execution
		return $exe->controller->execute(Array($cname,Array($exe)),$action,$parameter);
	}
}


?>