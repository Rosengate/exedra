<?php
namespace Exedra\Console\Wizard\Reference;

class Arcanist extends Reference
{
	public function initiateRegistry()
	{
		$this->register('routes', array(
			'description' => 'List routes',
			'options' => array('name', 'params', 'scan')
			));

		/*$this->register('config', array(
			'description' => 'List all configuration',
			'options' => array('scan')
			));*/
	}

	
}