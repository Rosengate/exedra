<?php

namespace Exedra\Runtime;

use Exedra\Contracts\Url\UrlGenerator;
use Exedra\Url\Url;

class Redirect implements UrlGenerator
{
    protected $response;

    protected $urlFactory;

    public function __construct(\Exedra\Http\Response $response, \Exedra\Url\UrlFactory $urlFactory)
    {
        $this->response = $response;

        $this->urlFactory = $urlFactory;
    }

    /**
     * Dynamically redirect to urlFactory callables
     * @param string $name
     * @param array $args
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
     * @param string $url
     * @return \Exedra\Http\Response
     */
    public function url($url)
    {
        return $this->to($url);
    }

    /**
     * Refresh the page.
     * alias to \Exedra\Http\Response::refresh()
     * @param int $time
     * @return \Exedra\Http\Response
     */
    public function refresh($time = 0)
    {
        return $this->response->refresh($time);
    }

    /**
     * Alias to to
     * @param string $url
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
     * @param string $route
     * @param array $params
     * @param mixed $query string
     * @return \Exedra\Http\Response|\Exedra\Url\Url|string
     */
    public function route($route = null, array $params = array(), array $query = array())
    {
        return $this->toRoute($route, $params, $query);
    }

    /**
     * Redirect by given route's name.
     * @param string $route
     * @param array $params
     * @param mixed $query
     * @return \Exedra\Http\Response
     */
    public function toRoute($route = null, $params = array(), $query = array())
    {
        if (!$route)
            return $this->refresh();

        $url = $this->urlFactory->create($route, $params, $query);

        return $this->to($url);
    }

    /**
     * @return string|Url
     */
    public function parent()
    {
        return $this->to($this->urlFactory->parent());
    }
}