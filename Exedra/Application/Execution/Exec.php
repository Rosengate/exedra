<?php
namespace Exedra\Application\Execution;

class Exec
{
	private $containerPointer	= 1;
	public $params	= Array();
	public $routeName;
	private $app;
	private $registered	= Array();

	public function __construct($routeName,$application,$params,$builders)
	{
		$this->routeName	= $routeName;
		$this->app	= $application;

		## Create params
		foreach($params as $key=>$val)
		{
			$this->params[$key]	= $val;
		}


		## builders.
		$builders($this);

		## registered builders.
		$this->registered	= Array(
			"url"=>Array("\Exedra\Application\Builder\Url",Array($this->app,$this)),
			"request"=>$this->app->request,
			"response"=>$this->app->exedra->httpResponse
			);
	}


	public function __get($property)
	{
		if(isset($this->registered[$property]))
		{
			$class	= $this->registered[$property];

			if(is_object($class))
			{
				$val	= $class;
			}
			else if(!$class[1])
			{
				$val	= $class[0];
			}
			else
			{
				$reflection	= new \ReflectionClass($class[0]);
				$obj	= $reflection->newInstanceArgs($class[1]);

				$val	= $obj;
			}

			## register as property.
			$this->$property	= $val;
			return $this->$property;
		}
	}

	public function next()
	{
		if(!$this->containers[$this->containerPointer])
			throw new \Exception("Exceeded execution container(s)");

		return call_user_func_array($this->containers[$this->containerPointer++], func_get_args());
	}

	public function param($name = null)
	{
		if(!$name)
			return $this->params;

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

	public function getRouteName()
	{
		return $this->routeName;
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

	public function execute($route,$parameter)
	{

	}
}