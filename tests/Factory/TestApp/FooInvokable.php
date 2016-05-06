<?php namespace TestApp;

class FooInvokable
{
	public function __invoke()
	{
		return 'bar';
	}
}


?>