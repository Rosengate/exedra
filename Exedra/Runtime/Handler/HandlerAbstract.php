<?php
namespace Exedra\Runtime\Handler;

abstract class HandlerAbstract implements HandlerInterface
{
	protected $exe;

	public function __construct(\Exedra\Runtime\Exe $exe)
	{
		$this->exe = $exe;
	}
}
