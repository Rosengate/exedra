<?php
namespace Exedra\Application\Execution\Factory;

class Exception extends \Exedra\Application\Factory\Exception
{
	/**
	 * @var \Exedra\Application\Execution\Exec
	 */
	protected $exe;

	public function __construct(\Exedra\Application\Execution\Exec $exe)
	{
		parent::__construct($exe->app);
		$this->exe = $exe;
	}

	public function create($message)
	{
		throw new \Exedra\Application\Execution\Exception\Exception($this->exe, $message);
	}
}