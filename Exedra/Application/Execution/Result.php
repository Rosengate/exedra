<?php
namespace Exedra\Application\Execution;

class Result
{
	private $containerPointer	= 1;
	public $params	= Array();

	public function __construct($params)
	{
		## assign each.
		foreach($params as $key=>$val)
		{
			$this->params[$key]	= $val;
		}
	}

	public function container()
	{
		if(!$this->containers[$this->containerPointer])
			throw new \Exception("Exceeded execution container(s)");

		return call_user_func_array($this->containers[$this->containerPointer++], func_get_args());
	}

	public function param($name)
	{
		$params	= is_array($name)?$name:explode(",",$name);

		if(count($params) > 1)
		{
			$new	= Array();
			foreach($params as $k)
			{
				$new[] = $this->params[$k];
			}

			return $new;
		}
		else
		{
			return $this->params[$params[0]];
		}
	}

	public function addParameter($key,$val = null)
	{
		if(is_array($key))
		{
			foreach($key as $k=>$v)
			{
				$this->addParameter($k,$v);
			}
		}
		else
		{
			## resolve the parameter.
			foreach($this->params as $k=>$v)
			{
				$key	= str_replace('{'.$k.'}',$v,$key);
				$val	= str_replace('{'.$k.'}', $v, $val);
			}

			// $this->params[$key]	= $val;

			## pointer.
			if(strpos($val, "&") === 0)
			{
				### create array by notation.
				$val	= str_replace("&","",$val);
				if(\Exedra\Functions\Arrays::hasByNotation($this->params,$val))
				{
					$ref	= \Exedra\Functions\Arrays::getByNotation($this->params,$val);
					\Exedra\Functions\Arrays::setByNotation($this->params,$key,$ref);
				}
			}
			else
			{
				\Exedra\Functions\Arrays::setByNotation($this->params,$key,$val);
			}

		}
	}

	public function addVariable($varName,$data)
	{
		if(!isset($this->$varName))
			$this->$varName	= Array();

		foreach($data as $key=>$val)
		{
			\Exedra\Functions\Arrays::setByNotation($this->$varName,$key,$val);
		}
	}
}