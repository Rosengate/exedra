<?php
namespace Exedra\Factory\Blueprint;

/**
 * Blueprint for View
 */

class View implements \ArrayAccess
{
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
	 * Prepared flag whether content have been rendered internally
	 * @var boolean flag
	 */
	protected $prepared = false;

	/**
	 * List of key of data required, before view can be rendered.
	 * @var array 
	 */
	protected $required 	= array();

	/**
	 * List of callbacks, of what can be done with the data, on callbackResolve()
	 */
	protected $callbacks	= array();

	public function __construct($path = null, $data = null)
	{
		if($path) $this->setPath($path);
	
		if($data) $this->set($data);
	
		// $this->loader = $loader;
	}

	/**
	 * Set data through array offset
	 * @param string key
	 * @param mixed value
	 */
	public function offsetSet($key, $value)
	{
		$this->data[$key] = $value;
	}

	/**
	 * Get data through array offset
	 * @param string key
	 * @return mixed
	 */
	public function offsetGet($key)
	{
		return $this->data[$key];
	}

	/**
	 * Check data existence through array offset
	 * @param string key
	 * @return bool
	 */
	public function offsetExists($key)
	{
		return isset($this->data[$key]);
	}

	/**
	 * Unset data through array offset
	 * @param string key
	 */
	public function offsetUnset($key)
	{
		unset($this->data[$key]);
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
	public function setCallback($key, $callback)
	{
		$this->callbacks[$key]	= $callback;
		return $this;
	}

	/**
	 * Resolve callback in rendering
	 * @return null
	 */
	protected function callbackResolve($data)
	{
		foreach($data as $key=>$val)
		{
			if(isset($this->callbacks[$key]))
				$data[$key]	= $this->callbacks[$key]($val);
		}

		return $data;
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
	 * Check whether view has this data or not
	 * @param string key
	 * @return boolean
	 */
	public function has($key)
	{
		return isset($this->data[$key]);
	}

	/**
	 * Set view data
	 * @param mixed key
	 * @param mixed value
	 * @return this
	 */
	public function set($key, $value = null)
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
	 * Get view data
	 * @param string key
	 * @return mixed
	 */
	public function get($key = null, $default = null)
	{
		if($key === null)
			return $this->data;

		return isset($this->data[$key]) ? $this->data[$key] : $default;
	}

	/**
	 * Handle invoked instance
	 */
	public function __invoke()
	{
		return $this->render();
	}

	/**
	 * Get view content
	 * @throws \Exedra\Application\Exception\Exception
	 */
	public function getContents()
	{
		if(!$this->isReady())
			return;

		ob_start();

		extract($this->callbackResolve($this->data));

		require $this->path;

		return ob_get_clean();
	}

	/**
	 * Check required data for rendering use.
	 * @return array
	 */
	protected function requirementCheck()
	{
		if(count($this->required) > 0)
		{
			// non-exists list
			$nonExist	= Array();
			foreach($this->required as $k)
			{
				if(!isset($this->data[$k]))
				{
					$nonExist[]	= $k;
				}
			}

			if(count($nonExist) > 0)
				return $nonExist;
		}

		return false;
	}

	/**
	 * Prepare the contents to be rendered
	 * Or execute any required variable
	 * @return null
	 */
	public function prepare()
	{
		// buffer this content for the next render
		$this->contents = $this->getContents();

		$this->prepared = true;

		return $this;
	}

	/**
	 * Check if view is ready
	 * @return boolean
	 * 
	 * @throws \Exedra\Exception\InvalidArgumentException
	 */
	protected function isReady()
	{
		if($requiredArgs = $this->requirementCheck())
			throw new \Exedra\Exception\InvalidArgumentException('View.render : Missing required argument(s) for view ['. $this->path .'] : '. implode(', ', $requiredArgs) .'');

		if($this->path == null)
			throw new \Exedra\Exception\InvalidArgumentException('Path was not set');

		return true;
	}

	/**
	 * Main rendering function, load the with loader function.
	 * Print the 
	 * @return mixed
	 * @throws \Exedra\Application\Exception\Exception
	 */
	public function render()
	{
		if($this->prepared === false)
			$this->prepare();

		$this->prepared = false;

		return $this->contents;
	}
}