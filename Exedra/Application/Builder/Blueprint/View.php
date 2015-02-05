<?php
namespace Exedra\Application\Builder\Blueprint;

class View
{
	private $path;
	public $data = Array();
	private $loader		= null;
	private $required 	= Array();
	private $callbacks	= Array();

	public function __construct($exe, $path = null,$data = null,$loader)
	{
		$this->exe = $exe;
		if($path) $this->setPath($path);
		if($data) $this->set($data);
		$this->loader = $loader;
	}

	/**
	 * Set required data for rendering.
	 * @param array @keys
	 * @return this
	 */
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

	/**
	 * Alias to setRequired
	 */
	public function setRequiredData($keys)
	{
		return $this->setRequired($keys);
	}

	/**
	 * Set callback for the given data key
	 * @param string key
	 * @param callback callback
	 * @return this
	 */
	public function setCallback($key,$callback)
	{
		$this->callbacks[$key]	= $callback;
		return $this;
	}

	/**
	 * Resolve callback in rendering
	 * @return null
	 */
	private function callbackResolve()
	{
		foreach($this->data as $key=>$val)
		{
			if(isset($this->callbacks[$key]))
				$this->data[$key]	= $this->callbacks[$key]($val);
		}
	}

	/**
	 * Set view path
	 * @param string path
	 * @return this
	 */
	public function setPath($path)
	{
		$this->path	= $path;
		return $this;
	}

	/**
	 * Set view data
	 * @param mixed key
	 * @param mixed value
	 * @return this
	 */
	public function set($key,$value = null)
	{
		if(!$value && is_array($key))
		{
			foreach($key as $k=>$v)
			{
				$this->set($k,$v);
			}
			return $this;
		}

		$this->data[$key]	= $value;

		return $this;
	}

	/**
	 * Check required data for rendering use.
	 * @return mixed
	 */
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

	/**
	 * Main rendering function, load the with loader function.
	 * @return null
	 */
	public function render()
	{
		## has required.
		if($requiredArgs = $this->requirementCheck())
			return $this->exe->exception->create('View.render : Missing required argument(s) for view ("'. $this->path .'") : '. $requiredArgs .'</b>');

		## resolve any related callback.
		$this->callbackResolve();

		if($this->path == null)
			return $this->exe->exception->create('View.render : path was not set (null)');

		/*if(!file_exists($this->path))
			return $this->exe->exception->create('View.render : Path "'. $this->path .'" does not exist');*/

		$this->loader->load(array('structure'=> 'view', 'path'=> $this->path) ,$this->data);
	}
}