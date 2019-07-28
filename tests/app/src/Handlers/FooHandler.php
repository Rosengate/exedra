<?php
namespace App\Handlers;

use Exedra\Contracts\Routing\ExecuteHandler;

class FooHandler implements ExecuteHandler
{
	public function validateHandle($pattern)
	{
		if(strpos($pattern, 'bar=') === 0)
			return true;

		return false;
	}

	public function resolveHandle($pattern)
	{
		return function($exe) use($pattern)
		{
			return $pattern;
		};
	}
}