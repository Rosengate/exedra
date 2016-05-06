<?php
namespace TestApp;

class FooProvider implements \Exedra\Provider\ProviderInterface
{
	public function register(\Exedra\Application $app)
	{
		$app['services']->add('foo', function()
		{
			return 'bar';
		});
	}
}