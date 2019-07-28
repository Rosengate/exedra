<?php
namespace App;

use Exedra\Contracts\Provider\Provider;

class FooProvider implements Provider
{
	public function register(\Exedra\Application $app)
	{
		$app['service']->add('foo', function()
		{
			return 'bar';
		});
	}
}