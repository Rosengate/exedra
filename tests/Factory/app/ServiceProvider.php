<?php
namespace App;

class ServiceProvider implements \Exedra\Provider\ProviderInterface
{
	public function register(\Exedra\Application $app)
	{
		$app['service']->add('bar', function()
		{
			return 'baz';
		});
	}
}