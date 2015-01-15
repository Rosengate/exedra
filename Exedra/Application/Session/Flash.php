<?php
namespace Exedra\Application\Session;

class Flash
{
	const KEY = 'flash';

	public function __construct(\Exedra\Application\Session\Session $session)
	{
		$this->session = $session;
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
			$this->session->set(self::KEY.'.'.$key,$val);
		}

		return $this;
	}

	/**
	 * Get the flash by the given key
	 * @param string key
	 * @param mixed default value
	 * @param mixed flash value
	 */
	public function get($key = null, $default = null)
	{
		if(!$key)
			return $this->session->get(self::KEY);

		if($default && !$this->has($key))
			return $default;
		
		return $this->session->get(self::KEY.'.'.$key);
	}

	/**
	 * Check if has the key
	 * @param string key
	 */
	public function has($key)
	{
		return $this->session->has(self::KEY.'.'.$key);
	}

	/**
	 * Clear the flash.
	 */
	public function clear()
	{
		return $this->session->destroy(self::KEY);
	}
}


?>