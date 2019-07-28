<?php
namespace App;

use Exedra\Contracts\Provider\Provider;

class ServiceProvider implements Provider
{
	public function register(\Exedra\Application $app)
	{
		$app['service']->add('bar', function()
		{
			return 'baz';
		});
	}
}