<?php
namespace Exedra\Application\Middleware\Handler;

abstract class HandlerAbstract implements \Exedra\Application\Execution\Handler\HandlerInterface
{
	public function __construct($name, \Exedra\Application\Execution\Exec $exe)
	{
		$this->name = $name;
		$this->exe = $exe;
	}
}
