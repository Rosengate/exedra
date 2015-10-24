<?php
namespace Exedra\Console\Wizard\Reference;

class Archmage extends Reference
{
	public function initiateRegistry()
	{
		$this->register('start', array(
			'description' => 'Create application',
			'options' => array()
			));
	}
}