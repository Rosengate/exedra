<?php
namespace Exedra\Application\Execution;

class Result
{
	private $containerPointer	= 1;

	public function __construct($params)
	{
		## assign each.
		foreach($params as $key=>$val)
		{
			$this->$key	= $val;
		}
	}

	public function container()
	{
		if(!$this->containers[$this->containerPointer])
			throw new \Exception("Exceeded execution container(s)");

		return call_user_func_array($this->containers[$this->containerPointer++], func_get_args());
	}
}