<?php
namespace Exedra\Runtime\Handler;

class Controller extends HandlerAbstract
{
	public function validate($pattern)
	{
		if(strpos($pattern, "controller=") === 0)
			return true;

		return false;
	}

	protected function parameterize($pattern)
	{
		return $pattern;
	}

	public function resolve($pattern)
	{
		return function($exe) use($pattern)
		{
			$controllerAction	= str_replace('controller=', '', $pattern);

			@list($cname, $action)	= explode('@', $controllerAction);

			$args	= array();

			if(preg_match('/{(.*?)}/', $cname, $match))
				$cname = str_replace($match[0], $exe->param($match[1]), $cname);

			if(preg_match('/{(.*?)}/', $action, $match))
			{
				$method = $exe->param($match[1]);

				if(is_array($method))
				{
					$args = $method;
					$method = array_shift($args);
					$action = str_replace($match[0], $method, $action);
				}
				else
				{
					$action = $method;
				}
			}

			$cname = implode('/', array_map(function($value)
			{
				return ucwords($value);
			}, explode('/', $cname)));

			return $exe->controller->execute(array($cname, array($exe)), $action, $args);
		};
	}
}
