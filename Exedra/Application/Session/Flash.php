<?php
namespace Exedra\Application\Session;

class Flash
{
	const BASE_KEY = 'flash';

	public function __construct(\Exedra\Application\Session\Session $session)
	{
		$this->session = $session;
	}

	/**
	 * Get base key for session
	 * @return string
	 */
	public function getBaseKey()
	{
		// hash to avoid conflict
		return md5(self::BASE_KEY);
	}

	/**
	 * Flash the value on the given key.
	 * @param mixed key
	 * @param mixed val
	 * @return this
	 */
	public function set($key, $val = array())
	{
		if(is_array($key))
		{
			foreach($key as $k=>$v)
			{
				$this->set($k,$v);
			}
		}
		else
		{
			$this->session->set($this->getBaseKey().'.'.$key,$val);
		}

		return $this;
	}

	/**
	 * Get the flash by the given key
	 * @param string key
	 * @param mixed default value
	 * @return mixed
	 */
	public function get($key = null, $default = null)
	{
		if(!$key)
			return $this->session->get($this->getBaseKey());

		if($default && !$this->has($key))
			return $default;
		
		return $this->session->get($this->getBaseKey().'.'.$key);
	}

	/**
	 * Check if has the key
	 * @param string key
	 * @return boolean
	 */
	public function has($key)
	{
		return $this->session->has($this->getBaseKey().'.'.$key);
	}

	/**
	 * Clear the flash.
	 * @return this;
	 */
	public function clear()
	{
		$this->session->destroy($this->getBaseKey());
		return $this;
	}
}


?>