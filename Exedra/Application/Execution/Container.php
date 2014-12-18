<?php
namespace Exedra\Application\Execution;

class Container
{
	private $registered;

	public function __construct($reg = null)
	{
		if($reg)
			$this->set($reg);
	}

	public function set($key,$val = null)
	{
		if(is_array($key))
		{
			foreach($key as $k=>$v)
				$this->set($k,$v);
		}
		else
		{
			$this->registered[$key]	= $val;
		}
	}

	public function __get($property)
	{
		if(isset($this->registered[$property]))
		{
			$class	= $this->registered[$property];

			if(is_object($class))
				return $class;
			
			if(!$class[1])
				return new $class[0];

			$reflection	= new \ReflectionClass($class[0]);
			$obj	= $reflection->newInstanceArgs($class[1]);

			return $obj;
		}
	}
}


?>