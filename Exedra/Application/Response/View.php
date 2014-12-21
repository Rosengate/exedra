<?php
namespace Exedra\Application\Response;

class View
{
	private $path;
	public $data = Array();
	private $loader		= null;
	private $required 	= Array();
	private $callbacks	= Array();

	public function __construct($path = null,$data = null,$loader)
	{
		if($path) $this->setPath($path);
		if($data) $this->set($data);
		$this->loader = $loader;
	}

	public function setRequired($keys)
	{
		if(is_string($keys))
		{
			$this->setRequired(explode(",",$keys));
		}
		else
		{
			foreach($keys as $key)
			{
				if(!in_array($key, $this->required))
					$this->required[] = $key;
			}
		}

		return $this;
	}

	public function setCallback($key,$callback)
	{
		$this->callbacks[$key]	= $callback;
	}

	private function callbackResolve()
	{
		foreach($this->data as $key=>$val)
		{
			if(isset($this->callbacks[$key]))
				$this->data[$key]	= $this->callbacks[$key]($val);
		}
	}

	public function setPath($path)
	{
		$this->path	= $path;
		return $this;
	}

	public function set($key,$v = null)
	{
		if(!$v && is_array($key))
		{
			foreach($key as $k=>$v)
			{
				$this->set($k,$v);
			}
			return $this;
		}

		$this->data[$key]	= $v;

		return $this;
	}


	private function requirementCheck()
	{
		if(count($this->required) > 0)
		{
			## non-exists list
			$nonExist	= Array();
			foreach($this->required as $k)
			{
				if(!isset($this->data[$k]))
				{
					$nonExist[]	= $k;
				}
			}

			if(count($nonExist) > 0)
				return implode(", ",$nonExist);
		}

		return false;
	}

	public function render()
	{
		## has required.
		if($requiredArgs = $this->requirementCheck())
			throw new \Exception("View.render : Missing required argument(s) for view ('".$this->path."') : <b>".$requiredArgs."</b>");

		## resolve any related callback.
		$this->callbackResolve();

		if($this->path == null)
			throw new \Exception("View.render : path was not set (null).");

		if(!file_exists($this->path))
			throw new \Exception("View.render : Path '".$this->path."' does not exists.");

		extract($this->data);
		$this->loader->load($this->path,$this->data);
	}
}