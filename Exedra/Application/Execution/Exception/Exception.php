<?php
namespace Exedra\Application\Execution\Exception;

class Exception extends \Exedra\Application\Exception\Exception
{
	/**
	 * @var \Exedra\Application\Execution\Exec exe
	 */
	public $exe;

	public function __construct($exe, $message)
	{
		$this->exe = $exe;
		parent::__construct($message);
	}

	public function getRouteParams()
	{
		return $this->exe->params();
	}

	public function getRouteName()
	{
		return $this->exe->getRoute(true);
	}
}