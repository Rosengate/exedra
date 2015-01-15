<?php
namespace Exedra\Application\Execution;

class Executor
{
	protected $controller;
	protected $middlewares;
	
	public function __construct(\Exedra\Application\Execution\Binder $binder, \Exedra\Application\Structure\Loader $loader)
	{
		$this->binder = $binder;
		$this->loader = $loader;
	}

	private function resolveMiddleware(&$middlewares)
	{
		foreach($middlewares as $no=>$middleware)
		{
			if(is_string($middlewares[$no]))
			{
				if($this->loader->isLoadable($middlewares[$no]))
				{
					$closure = $this->loader->load($middlewares[$no]);
					if($closure instanceof \Closure)
					{
						$middlewares[$no] = $closure;
					}
					else
					{
						return $exe->exception->create("The file located in '".$middlewares[$no]."' must be a returned closure.");
					}
				}
				// has middleware builder.
				else if(strpos($middlewares[$no], "middleware=") === 0)
				{
					$middleware = str_replace("middleware=", "", $middlewares[$no]);

					$atoms = explode("@", $middleware);
					
					$middleware = $atoms[0];

					// if no method was passed, will use handle as method name.
					$method = isset($atoms[1]) ? $atoms[1] : "handle";

					// create a handler.
					$middlewares[$no] = function($exe) use($middleware, $method) {$exe->middleware->create($middleware)->$method($exe);};
				}
			}
		}
	}

	public function execute($execution,$exe)
	{
		if(is_object($execution))
		{
			if($this->binder->hasBind("middleware"))
			{
				$exe->middlewares = new \ArrayIterator($this->binder->getBind("middleware"));

				$this->resolveMiddleware($exe->middlewares);

				// set the last of the container as execution.
				$exe->middlewares->offsetSet($exe->middlewares->count(), $execution);

				$exe->middlewares->rewind();

				$exe = $exe->middlewares[0]($exe);
			}
			else
			{
				$exe= $execution($exe);
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