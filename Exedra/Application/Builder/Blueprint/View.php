<?php
namespace Exedra\Application\Builder\Blueprint;

/**
 * Blueprint for View
 */

class View
{
	/**
	 * An Exception manager.
	 * @var \Exedra\Application\Builder\Exception
	 */
	protected $exceptionBuilder;

	/**
	 * Path for this view.
	 * @var string
	 */
	protected $path;

	/**
	 * Data for this view
	 * @var array
	 */
	public $data = array();

	/**
	 * Loader to be used.
	 * @var \Exedra\Loader
	 */
	protected $loader		= null;

	/**
	 * List of key of data required, before view can be rendered.
	 * @var array 
	 */
	protected $required 	= array();

	/**
	 * List of callbacks, of what can be done with the data, on callbackResolve()
	 */
	protected $callbacks	= array();

	public function __construct(\Exedra\Application\Builder\Exception $exceptionBuilder, $path = null, $data = null, \Exedra\Loader $loader)
	{
		$this->exceptionBuilder = $exceptionBuilder;
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
	 * @param mixed key
	 * @return this
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
	protected function callbackResolve()
	{
		foreach($this->data as $key=>$val)
		{
			if(isset($this->callbacks[$key]))
				$this->data[$key]	= $this->callbacks[$key]($val);
		}
	}

	/**
	 * Return view current full path
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
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
	protected function requirementCheck()
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
	 * @throws \Exedra\Application\Exception\Exception
	 */
	public function render()
	{
		## has required.
		if($requiredArgs = $this->requirementCheck())
			return $this->exceptionBuilder->create('View.render : Missing required argument(s) for view ("'. $this->path .'") : '. $requiredArgs .'</b>');

		## resolve any related callback.
		$this->callbackResolve();

		if($this->path == null)
			return $this->exceptionBuilder->create('View.render : path was not set (null)');

		$this->loader->load(array('structure'=> 'view', 'path'=> $this->path) ,$this->data);
	}
}