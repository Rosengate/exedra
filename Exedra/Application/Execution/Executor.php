<?php
namespace Exedra\Application\Execution;

class Executor
{
	private $controller	= null;
	
	public function __construct( $controller, $binder, $app )
	{
		$this->controller 	= $controller;
		$this->binder		= $binder;
		$this->app	= $app;
	}

	public function execute($execution,$exe)
	{
		if(is_object($execution))
		{
			if($this->binder->hasBind("middleware"))
			{
				$middlewares			= $this->binder->getBind("middleware");

				$exe->containers			= $middlewares;

				## set the last of the container as execution.
				$exe->containers[count($middlewares)]	= $execution;

				// has ':' as splitter between load path and filename
				if(is_string($middlewares[0]))
				{
					if($this->app->loader->isLoadable($middlewares[0]))
					{
						$closure = $this->app->loader->load($middlewares[0]);
						if($closure instanceof \Closure)
						{
							$middlewares[0] = $closure;
						}
						else
						{
							return $exe->exception->create("The file located in '".$middlewares[0]."' must be a returned closure.");
						}
					}
					// has middleware=, if no method was passed, will use handle as method name.
					else if(strpos($middlewares[0], "middleware=") === 0)
					{
						$middleware = str_replace("middleware=", "", $middlewares[0]);

						$atoms = explode("@", $middleware);
						
						$middleware = $atoms[0];
						$handler = isset($atoms[1]) ? $atoms[1] : "handle";

						$middlewares[0] = function($exe) use($middleware, $handler) {$exe->middleware->create($middleware)->$handler($exe);};
					}
				}


				$exe	= $middlewares[0]($exe);
			}
			else
			{
				$exe	= $execution($exe);
			}

			return Resolver::resolve($exe);
		}

		## look for execution keyword.
		if(is_string($execution))
		{
			// has controller=
			if(strpos($execution, "controller=") === 0)
			{
				$controllerAction	= str_replace("controller=", "", $execution);

				$handler	= function($exe) use($controllerAction,$exe)
				{
					return $this->executeController($controllerAction,$exe);
				};

				## recursive to use the main execution.
				return $this->execute($handler,$exe);
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