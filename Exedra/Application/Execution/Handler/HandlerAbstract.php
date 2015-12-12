<?php
namespace Exedra\Application\Execution\Handler;

abstract class HandlerAbstract implements HandlerInterface
{
	protected $name;

	public function __construct($name, \Exedra\Application\Execution\Exec $exe)
	{
		$this->name = $name;
		$this->exe = $exe;
	}

	/**
	 * Replace everything in a pattern with this execution parameters.
	 * @param mixed pattern
	 */
	protected function parameterize($pattern)
	{
		if(is_string($pattern))
		{
			$params = $this->exe->params();

			$unmatched = array();
			$pattern = preg_replace_callback('/{(.*?)}/', function($match) use($params, &$unmatched)
			{
				@list($name, $optional) = explode('|', $match[1], 2);
				
				if(!isset($params[$name]))
				{
					if($optional)
						return $optional;

					if(!in_array($name, $unmatched))
						$unmatched[] = $name;
					
					return;
				}

			    return $params[$name];

			}, $pattern);

			if(count($unmatched) > 0)
				return $this->exe->exception->create('Missing parameter(s) for execution handler '.$this->name.' : '.implode(', ', $unmatched).'');
		}

		return $pattern;
	}

	public function prepare($pattern)
	{
		return $this->resolve($this->parameterize($pattern));
	}
}
