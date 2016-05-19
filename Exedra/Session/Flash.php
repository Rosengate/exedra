<?php
namespace Exedra\Session;

/**
 * By default flash data ONLY cleared on instantiated.
 */
class Flash
{
	const BASE_KEY = 'flash';

	/**
	 * Initialized flag whether data has been init
	 * @var boolean initialized
	 */
	protected $initialized = false;

	/**
	 * Flash data initialized from session
	 * @var array data
	 */
	protected $data = array();

	public function __construct(\Exedra\Session\Session $session)
	{
		$this->session = $session;

		$this->initialize();
	}

	/**
	 * Initialize data by call
	 * Although instantiating this instance will basically initialize the data.
	 */
	public function initialize()
	{
		if($this->initialized)
			return;

		$this->data = $this->session->get(self::BASE_KEY, array());

		$this->clear();

		$this->initialized = true;
	}

	/**
	 * Flash the value on the given key.
	 * @param mixed key
	 * @param mixed val
	 * @return this
	 */
	public function set($key, $value)
	{
		$this->session->set(self::BASE_KEY.'.'.$key, $value);

		return $this;
	}

	/**
	 * Get all flashes
	 * @return array
	 */
	public function getAll()
	{
		return $this->data;
	}

	/**
	 * Get flash data
	 * @return default if array_key isn't exists.
	 * @param string key
	 * @param mixed default value
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		if(!array_key_exists($key, $this->data) && $default)
			return $default;

		return $this->data[$key];
	}

	/**
	 * Check if has the key
	 * @param string key
	 * @return boolean
	 */
	public function has($key)
	{
		return isset($this->data[$key]);
	}

	/**
	 * Clear the flash.
	 * @return this;
	 */
	public function clear()
	{
		$this->session->destroy(self::BASE_KEY);

		return $this;
	}
}


?>