<?php
namespace Exedra\Routing;

class Convenient extends \Exedra\Routing\Level implements \Exedra\Routing\RoutableInterface
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

	public function get($path = '/')
	{
		return $this->method('get', $path);
	}

	public function post($path = '/')
	{
		return $this->method('post', $path);
	}

	public function put($path = '/')
	{
		return $this->method('put', $path);
	}

	public function delete($path = '/')
	{
		return $this->method('delete', $path);
	}

	public function patch($path = '/')
	{
		return $this->method('patch', $path);
	}

	public function any($path = '/')
	{
		return $this->method(null, $path);
	}

	public function path($path = '/')
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