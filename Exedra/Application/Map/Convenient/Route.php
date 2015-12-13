<?php
namespace Exedra\Application\Map\Convenient;

class Route extends \Exedra\Application\Map\Route
{
	
	public function execute($hander)
	{
		return $this->setExecute($hander);
	}

	public function group(\Closure $callback)
	{
		return $this->setSubroutes($callback);
	}
}