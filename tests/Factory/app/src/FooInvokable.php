<?php namespace App;

class FooInvokable
{
	public function __invoke()
	{
		return 'bar';
	}
}


?>