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

	public function execute($execution,$params,$app)
	{
		if(is_object($execution))
		{
			if($this->binder->hasBind("execute"))
			{
				$parent_execution	= $this->binder->getBind("execute");
				return $parent_execution($params,$execution);
			}
			return $execution($params);
		}

		## look for execution keyword.
		if(is_string($execution))
		{
			## controller.
			if(strpos($execution, "controller=") === 0)
			{
				return $this->executeController(str_replace("controller=", "", $execution),$params,$app);
			}
		}
	}

	private function executeController($controllerAction,$params = Array(),$app)
	{
		list($cname,$action)	= explode("@",$controllerAction);
		if($params)
		{
			foreach($params as $key=>$val)
			{
				$cname	= str_replace('{'.$key.'}', $val, $cname);
				$action	= str_replace('{'.$key.'}', $val, $action);
			}
		}

		if($this->binder->hasBind("controller.execute"))
		{
			$controller_execute	= $this->binder->getBind("controller_execute");
			return $controller_execute($cname,$action);
		}
		return $this->controller->execute(Array($cname,Array($app)),$action);
	}
}


?>