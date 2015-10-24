<?php
namespace Exedra\Console\Wizard\Reference;

abstract class Reference
{
	protected $registry;

	public function __construct()
	{
		$this->initiateRegistry();
	}

	public function register($name, array $definitions)
	{
		$this->registry[$name] = $definitions;
	}

	public function hasCommand($name)
	{
		return isset($this->registry[$name]);
	}

	public function getRegistry()
	{
		return $this->registry;
	}

	public function parse()
	{

	}
}