<?php

namespace Exedra\Url;

use Exedra\Contracts\Url\UrlGenerator as UrlGeneratorInterface;
use Exedra\Exception\NotFoundException;
use Exedra\Http\ServerRequest;
use Exedra\Routing\Group;
use MongoDB\Driver\Server;
use Psr\Http\Message\UriInterface;

/**
 * A route oriented url generator
 */
class UrlGenerator implements UrlGeneratorInterface
{
    /**
     * Absolute Base url
     * @var string
     */
    protected $baseUrl;

    /**
     * Request instance
     * @var \Exedra\Http\ServerRequest|null
     */
    protected $request;

    protected $callables = array();
    /**
     * Application map
     * @param \Exedra\Routing\Group
     */
    protected $map;

    /**
     * @var null|Group $rootRouter
     */
    protected $rootRouter;

    public function __construct(
        \Exedra\Routing\Group $router,
        \Exedra\Http\ServerRequest $request = null,
        $appUrl = null,
        array $callables = array())
    {
        $this->map = $router;
        $this->rootRouter = $router->getRootGroup();
        $this->request = $request;
        $this->setBase($appUrl ?: ($request ? $request->getUri()->getScheme() . '://' . $request->getUri()->getAuthority() : null));
        $this->callables = $callables;
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Get previous url (referer)
     * @return string
     * @throws NotFoundException
     */
    public function previous()
    {
        if (!$this->request)
            throw new NotFoundException('Http Request does not exist.');

        $referer = $this->request->getHeaderLine('referer');

        return $referer ?: false;
    }

    public function __call($name, array $args = array())
    {
        if (!isset($this->callables[$name]))
            throw new NotFoundException('Method / callable [' . $name . '] does not exists');

        return call_user_func_array($this->callables[$name], array_merge(array($this), $args));
    }

    /**
     * Register a callables
     * @param string $name
     * @param \Closure $callable
     * @return $this
     */
    public function addCallable($name, \Closure $callable)
    {
        $this->callables[$name] = $callable;

        return $this;
    }

    /**
     * Get all callables
     *
     * @return \Closure[]
     */
    public function getCallables()
    {
        return $this->callables;
    }

    /**
     * Get url prefixed with $baseUrl
     * @param string $path (optional)
     * @param UriInterface|null $baseUri
     * @return string
     */
    public function base($path = null, UriInterface $baseUri = null)
    {
        $baseUrl = $baseUri ? (string) $baseUri : $this->baseUrl;

        return ($baseUrl ? rtrim($baseUrl, '/') . '/' : '/') . ($path ? trim($path, '/') : '');
    }

    /**
     * Alias to base()
     * @param string $path
     * @return string
     */
    public function to($path = null)
    {
        return $this->base($path);
    }

    /**
     * Set $baseUrl
     * @param string $baseUrl
     * @return $this
     */
    public function setBase($baseUrl)
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    /**
     * Create url by route name
     * relative to the given \Exedra\Routing\Group
     * Force an absolute route, by prefixing with '@'
     * @param string $routeName
     * @param array $data
     * @param mixed $query (uri query string)
     * @return string
     * @throws NotFoundException
     */
    public function create($routeName, array $data = array(), array $query = array())
    {
        if (strpos($routeName, '@') === 0)
            $route = $this->rootRouter->findRoute(substr($routeName, 1));
        else
            $route = $this->map->findRoute($routeName);

        if (!$route)
            throw new NotFoundException('Unable to find route [' . $routeName . ']');

        $path = $route->getAbsolutePath($data);

//        $url = $this->base($path, $route->getBaseUri()) . ($query ? '?' . http_build_query($query) : null);

        return $this->parameterize($this->base($path, $route->getBaseUri()) . ($query ? '?' . http_build_query($query) : null), $data);
    }

    protected function parameterize($url, array $data)
    {
        foreach ($data as $key => $value) {
            $url = str_replace('{' . $key . '}', $value, $url);
        }

        return $url;
    }

    public function parent()
    {
        $route = $this->map->getUpperRoute();

        if (!$route)
            throw new NotFoundException('Unable to find the parent route for the current route.');

        $route = $route->getAbsoluteName();

        return $this->create('@' . $route);
    }

    /**
     * Alias to create()
     * @param string $routename
     * @param array $data
     * @param array $query
     *
     * @return Url|string
     */
    public function route($routeName, array $data = array(), array $query = array())
    {
        return $this->create($routeName, $data, $query);
    }

    /**
     * Get current url
     * @param array $query
     * @return string
     * @throws \Exedra\Exception\InvalidArgumentException
     */
    public function current(array $query = array())
    {
        if (!$this->request)
            throw new \Exedra\Exception\InvalidArgumentException('Http Request does not exist.');

        $uri = $this->request->getUri();

        if (count($query) > 0) {
            // append query to uri
            if ($uri->getQuery())
                $query = http_build_query($query);
            else
                $query = '?' . http_build_query($query);
        } else {
            $query = '';
        }

        return $this->request->getUri() . $query;
    }
}