<?php
namespace Exedra\Runtime;

class Redirect implements \Exedra\Factory\UrlInterface
{
	public function __construct(\Exedra\Http\Response $response, \Exedra\Factory\Url $urlFactory)
	{
		$this->response = $response;

		$this->urlFactory = $urlFactory;
	}

	/**
	 * Dynamically redirect to urlFactory callables
	 * @param string name
	 * @param array args
	 * @return \Exedra\Http\Response
	 */
	public function __call($name, array $args = array())
	{
		$url = $this->urlFactory->__call($name, $args);

		return $this->to($url);
	}

	/**
	 * Redirect to referer
	 * @return \Exedra\Http\Response
	 */
	public function previous()
	{
		$url = $this->urlFactory->previous();

		return $this->to($url);
	}

	/**
	 * Alias to to(url)
	 * @param string url
	 */
	public function url($url)
	{
		return $this->to($url);
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
	 * Alias to to
	 * @param string url
	 * @return \Exedra\Http\Response
	 */
	public function to($url)
	{
		return $this->response->redirect($url);
	}

	/**
	 * Redirect to current url.
	 * @return \Exedra\Http\Response
	 */
	public function current()
	{
		$url = $this->urlFactory->current();

		return $this->to($url);
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

		return $this->to($url);
	}
}