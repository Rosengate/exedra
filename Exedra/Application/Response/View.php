<?php
namespace Exedra\Application\Response;

class View
{
	private $path;
	private $data = Array();

	public function __construct($path = null,$data = null)
	{
		if($path) $this->setPath($path);
		if($data) $this->set($data);
	}

	public function setPath($path)
	{
		$this->path	= $path;
	}

	public function set($key,$v = null)
	{
		if(!$v)
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

	public function render()
	{
		if($this->path == null)
		{
			throw new Exception("View path was not set.");
		}

		extract($this->data);
		return require_once $this->path;
	}
}