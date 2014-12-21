<?php
namespace Exedra\Application\Response;

class Redirect
{
	public function __construct(\Exedra\Application\Execution\Exec $exe)
	{
		$this->exe = $exe;
	}

	public function toUrl($url)
	{
		header("location:$url");die;
	}

	public function refresh()
	{
		return $this->to($this->exe->getRoute(), $this->exe->getParams());
	}

	public function to($route = null, $params = array())
	{
		if(!$route)
			return $this->refresh();

		$url = $this->exe->url->create($route, $params);

		return $this->toUrl($url);
	}
}