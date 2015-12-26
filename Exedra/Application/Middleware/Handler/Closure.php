<?php
namespace Exedra\Application\Middleware\Handler;

/**
 * Simply a Closure based middleware
 */
class Closure extends HandlerAbstract
{
	public function validate($pattern)
	{
		if($pattern instanceof \Closure)
			return true;

		return false;
	}

	public function resolve($pattern)
	{
		return $pattern;
	}
}

?>