<?php
namespace Exedra\Routing;

class Convenient extends \Exedra\Routing\Level
{
	/**
	 * Create a route by given methods
	 * @param string|array method
	 * @param string path
	 * @return \Exedra\Routing\Route
	 */
	public function method($method = null, $path = '/')
	{
		$parameters = array();

		$parameters['path'] = $path;

		if($method)
			$parameters['method'] = $method;

		$route = $this->factory->createRoute($this, null, $parameters);

		$this->addRoute($route);

		return $route;
	}

	public function get($path = null)
	{
		return $this->method('get', $path);
	}

	public function post($path = null)
	{
		return $this->method('post', $path);
	}

	public function put($path = null)
	{
		return $this->method('put', $path);
	}

	public function delete($path = null)
	{
		return $this->method('delete', $path);
	}

	public function any($path = null)
	{
		return $this->method(null, $path);
	}

	/**
	 * A level invoke to conveniently
	 * create an empty route with the optional name
	 * @param string name
	 * @return \Exedra\Routing\Route
	 */
	public function offsetGet($name)
	{
		if(isset($this->routeCache[$name]))
			return $this->routeCache[$name];

		$route = $this->factory->createRoute($this, $name, array());

		$this->addRoute($route);

		return $this->routeCache[$name] = $route;
	}
}