<?php
namespace Exedra\Application\Execution;

class Resolver
{
	public static function resolve($result)
	{
		if(is_object($result))
		{
			$type	= get_class($result);

			switch($type)
			{
				case "Exedra\Application\Builder\Blueprint\View":
					return $result->render();
				break;
			}
		}
		else
		{
			return $result;
		}
	}
}


?>