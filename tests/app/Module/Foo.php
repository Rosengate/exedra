<?php
namespace App\Module;

class Foo extends \Exedra\Module\Module
{
	public function setUp()
	{
		parent::setUp();

		$this->services['service']->add('bar', function()
		{
			return 'baz';
		});
	}
}