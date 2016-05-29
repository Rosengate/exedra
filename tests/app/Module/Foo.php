<?php
namespace App\Module;

class Foo extends \Exedra\Module\Module
{
	public function boot()
	{
		parent::boot();

		$this->services['services']->add('bar', function()
		{
			return 'baz';
		});
	}
}