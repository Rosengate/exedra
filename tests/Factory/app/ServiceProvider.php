<?php
namespace App;

class ServiceProvider implements \Exedra\Provider\ProviderInterface
{
	public function register(\Exedra\Application $app)
	{
		$app['services']->add('bar', function()
		{
			return 'baz';
		});
	}
}