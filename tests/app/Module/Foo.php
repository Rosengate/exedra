<?php
namespace App\Module;

class Foo extends \Exedra\Module\Module
{
	public function setUp(\Exedra\Path $path)
	{
		parent::setUp($path);

		$this->services['service']->add('bar', function()
		{
			return 'baz';
		});
	}
}