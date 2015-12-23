<?php
namespace Exedra\Application\Response;

class Redirect
{
	public function __construct(\Exedra\Application\Execution\Exec $exe)
	{
		$this->exe = $exe;
	}

	/**
	 * Redirect to url
	 * @param string url
	 * @return redirection
	 */
	final public function toUrl($url)
	{
		return $this->exe->response->redirect($url);
	}

	/**
	 * Do a session flash
	 * @param mixed key
	 * @param mixed val
	 * @return this
	 */
	public function flash($key, $val = null)
	{
		$this->exe->flash->set($key, $val);
		return $this;
	}

	/**
	 * Refresh the page.
	 * @return redirection
	 */
	public function refresh()
	{
		return $this->to($this->exe->getRoute(), $this->exe->params());
	}

	/**
	 * Alias to toRoute
	 * @param string route
	 * @param array route named params
	 * @param mixed query string
	 */
	public function to($route = null, $params = array(), $query = array())
	{
		return $this->toRoute($route, $params, $query);
	}

	/**
	 * Redirect by given route's name.
	 * @param string route
	 * @param array params
	 * @param mixed query
	 */
	public function toRoute($route = null, $params = array(), $query = array())
	{
		if(!$route)
			return $this->refresh();

		$url = $this->exe->url->create($route, $params, $query);

		return $this->toUrl($url);
	}
}