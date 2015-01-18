<?php
namespace Exedra\Application\Execution;

class Binder
{
	private $list;

	public function __construct(array $binds = array())
	{
		if($binds)
			foreach($binds as $name=>$callback)
				$this->bind($name,$callback);
	}

	public function bind($name,$callback)
	{
		$this->list[$name]	= $callback;
	}

	public function hasBind($name)
	{
		return isset($this->list[$name]);
	}

	public function getBind($name)
	{
		return $this->list[$name];
	}
}

?>