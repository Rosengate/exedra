<?php namespace Foo;

class FooInvokable
{
	public function __invoke()
	{
		return 'bar';
	}
}


?>