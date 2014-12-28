<?php
namespace Exedra\Application\Session;

class Session
{
	public function __construct(&$storage = null)
	{
		## set storage. default use php _SESSION, if not passed through constructor param.
		if($storage)
		{
			$this->storage = &$storage;
		}
		else
		{
			session_start();
			$this->storage = &$_SESSION;
		}
	}

	// not sure but look's ugly because this is the only use of static :X
	public static function hasStarted()
	{
		return session_status() != PHP_SESSION_NONE;
	}

	public function set($key,$value)
	{
		\Exedra\Functions\Arrays::setByNotation($this->storage,$key,$value);
		return $this;
	}

	public function get($key)
	{
		return \Exedra\Functions\Arrays::getByNotation($this->storage,$key);
	}

	public function has($key)
	{
		return \Exedra\Functions\Arrays::hasByNotation($this->storage,$key);
	}

	public function destroy($key = null)
	{
		\Exedra\Functions\Arrays::deleteByNotation($this->storage,$key);
		return $this;
	}
}

?>