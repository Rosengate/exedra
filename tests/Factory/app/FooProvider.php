<?php
namespace App;

class FooProvider implements \Exedra\Provider\ProviderInterface
{
	public function register(\Exedra\Application $app)
	{
		$app['service']->add('foo', function()
		{
			return 'bar';
		});
	}
}