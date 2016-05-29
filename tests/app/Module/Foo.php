<?php
namespace App\Module;

class Foo extends \Exedra\Module\Module
{
	public function setUp()
	{
		parent::setUp();

		$this->services['services']->add('bar', function()
		{
			return 'baz';
		});
	}
}