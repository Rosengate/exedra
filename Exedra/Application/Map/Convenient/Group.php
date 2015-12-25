<?php
namespace Exedra\Application\Map\Convenient;

class Group extends \Exedra\Application\Map\Level
{
	/**
	 * Convenient route adding method
	 * @param string|array method
	 * @param string path
	 * @return \Exedra\Application\Map\Convenient\Route
	 */
	public function add($method = null, $path = null, $params = null)
	{
		$parameters = array();

		$parameters['path'] = $path === null ? '' : $path;

		if($method)
			$parameters['method'] = $method;

		if($params)
		{
			if(is_array($params))
				$parameters = array_merge($parameters, $params);
			else
				$parameters['execute'] = $params;
		}

		$route = $this->factory->createRoute($this, null, $parameters);

		$this->addRoute($route);

		return $route;
	}

	public function get($path = null, $params = null)
	{
		return $this->add('get', $path, $params);
	}

	public function post($path = null, $params = null)
	{
		return $this->add('post', $path, $params);
	}

	public function put($path = null, $params = null)
	{
		return $this->add('put', $path, $params);
	}

	public function delete($path = null, $params = null)
	{
		return $this->add('put', $path, $params);
	}

	public function any($path = null, $params = null)
	{
		return $this->add(null, $path, $params);
	}
}