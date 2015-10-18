<?php
namespace Exedra\Application\Session;

/**
 * A simple session manager based on php _SESSION (default)
 */
class Session
{
	/**
	 * Referenced storage for session data
	 */
	protected $storage;

	/**
	 * Prefix
	 */
	protected $prefix;

	public function __construct(&$storage = null)
	{
		## set storage. default use php _SESSION, if not passed through constructor param.
		if($storage !== null)
		{
			$this->storage = &$storage;
		}
		else
		{
			if(!isset($_SESSION))
				session_start();

			$this->storage = &$_SESSION;
		}
	}

	/**
	 * Point the current storage to the prefixing point.
	 * This session manager will later point every session operation (has, get, set, getAll, destroy) on this key level
	 * Another prefix will not to a new prefix, except append to the current reference.
	 * @return \Exedra\Application\Session
	 */
	public function setPrefix($prefix)
	{
		$storage = &\Exedra\Functions\Arrays::getReferenceByNotation($this->getStorage(), $prefix);

		$this->set($prefix, $storage);

		$this->storage = &$storage;

		return $this;
	}

	/**
	 * not sure but look's ugly because this is the only use of static :X
	 * @return bool
	 */
	public static function hasStarted()
	{
		return session_status() != PHP_SESSION_NONE;
	}

	/**
	 * Get referenced storage variable
	 * @return &storage
	 */
	public function &getStorage()
	{
		return $this->storage;
	}

	/**
	 * Set a session by the given key.
	 * @param string key
	 * @param mixed value
	 * @return \Exedra\Application\Session
	 */
	public function set($key,$value)
	{
		\Exedra\Functions\Arrays::setByNotation($this->getStorage(),$key,$value);

		return $this;
	}

	/**
	 * Get the session by the given key.
	 * @param string key
	 * @param session value
	 * @return mixed
	 */
	public function get($key)
	{
		return \Exedra\Functions\Arrays::getByNotation($this->getStorage(), $key);
	}

	/**
	 * Get everything within storage
	 * @return mixed
	 */
	public function getAll()
	{
		return $this->storage;
	}

	/**
	 * Check whether session exist by the given key.
	 * @param string key
	 * @return boolean
	 */
	public function has($key)
	{
		return \Exedra\Functions\Arrays::hasByNotation($this->storage,$key);
	}

	/**
	 * Destroy session, or only the given key.
	 * @param string key
	 * @return \Exedra\Application\Session
	 */
	public function destroy($key = null)
	{
		\Exedra\Functions\Arrays::deleteByNotation($this->storage,$key);
		return $this;
	}
}

?>