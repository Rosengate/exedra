<?php
namespace Exedra\Application\Session;

class Flash
{
	public function __construct(\Exedra\Application\Application $app)
	{
		$this->session = $app->session;
	}

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
			$this->session->set("flash.$key",$val);
		}
	}

	public function get($key = null, $default = null)
	{
		if(!$key)
			return $this->session->get("flash");

		if($default && !$this->has($key))
			return $default;
		
		return $this->session->get("flash.$key");
	}

	public function has($key)
	{
		return $this->session->has("flash.$key");
	}

	public function clear()
	{
		return $this->session->destroy("flash");
	}
}


?>