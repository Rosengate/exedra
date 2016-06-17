<?php
namespace Exedra\Runtime;

class Redirect
{
	public function __construct(\Exedra\Http\Response $response, \Exedra\Factory\Url $urlFactory)
	{
		$this->response = $response;

		$this->urlFactory = $urlFactory;
	}

	/**
	 * Redirect to url
	 * Set header redirect to the url
	 * @param string url
	 * @return redirection
	 */
	public function toUrl($url)
	{
		return $this->response->redirect($url);
	}

	/**
	 * Alias to toUrl(url)
	 * @param string url
	 */
	public function url($url)
	{
		return $this->toUrl($url);
	}

	/**
	 * Refresh the page.
	 * alias to \Exedra\Http\Response::refresh()
	 * @return refresh
	 */
	public function refresh($time = 0)
	{
		return $this->response->refresh($time);
	}

	/**
	 * Alias to toRoute
	 * @param string route
	 * @param array route named params
	 * @param mixed query string
	 */
	public function to($route = null, array $params = array(), array $query = array())
	{
		return $this->toRoute($route, $params, $query);
	}

	/**
	 * Alias to toRoute
	 * @param string route
	 * @param array route named params
	 * @param mixed query string
	 */
	public function route($route = null, array $params = array(), array $query = array())
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

		$url = $this->urlFactory->create($route, $params, $query);

		return $this->toUrl($url);
	}
}