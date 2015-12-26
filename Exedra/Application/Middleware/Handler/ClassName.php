<?php
namespace Exedra\Application\Middleware\Handler;

/**
 * Handle absolute class name based middleware
 * Is supposed to run at the last order, on default resolve order.
 */
class ClassName extends HandlerAbstract
{
	public function validate($pattern)
	{
		return is_string($pattern);
	}

	public function resolve($middleware)
	{
		$method = 'handle';

		return function($exe) use($middleware, $method)
		{
			$middleware = new $middleware;
			return $middleware->$method($exe);
		};
	}
}
