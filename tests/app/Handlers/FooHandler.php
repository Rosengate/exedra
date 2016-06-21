<?php
namespace App\Handlers;

class FooHandler implements \Exedra\Runtime\Handler\HandlerInterface
{
	public function __construct(\Exedra\Runtime\Exe $exe)
	{
		$this->exe = $exe;
	}

	public function validate($pattern)
	{
		if(strpos($pattern, 'bar=') === 0)
			return true;

		return false;
	}

	public function resolve($pattern)
	{
		return function($exe) use($pattern)
		{
			return $pattern;
		};
	}
}