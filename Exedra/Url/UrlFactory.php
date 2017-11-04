<?php
namespace Exedra\Url;

/**
 * A route oriented url generator
 */
class UrlFactory extends UrlGenerator
{
    /**
     * Url filters
     * @var array|\Closure[]
     */
    protected $filters = array();

    public function __construct(
        \Exedra\Routing\Group $router,
        \Exedra\Http\ServerRequest $request = null,
        $appUrl = null,
        array $filters = array(),
        array $callables = array()
    )
    {
        $this->filters = $filters;

        parent::__construct($router, $request, $appUrl, $callables);
    }

    /**
     * Add Url filter
     *
     * @param \Closure $filter
     * @return $this
     */
    public function addFilter(\Closure $filter)
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * @return array|\Closure[]
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Get previous url (referer)
     * @return Url|false
     * @throws \Exedra\Exception\NotFoundException
     */
    public function previous()
    {
        $url = parent::previous();

        if(!$url)
            return false;

        return $this->createUrl($url);
    }

    /**
     * @param string $url
     * @return Url|string
     */
    protected function createUrl($url)
    {
        $url = new Url($url);

        // apply filters
        if($this->filters)
            foreach($this->filters as $filter)
                $url = $filter($url);

        return $url;
    }

    /**
     * @param $name
     * @param array $args
     * @return Url
     */
    public function __call($name, array $args = array())
    {
        return $this->createUrl(parent::__call($name, $args));
    }

    /**
     * Get url prefixed with $baseUrl
     * @param string $path (optional)
     * @return Url
     */
    public function base($path = null)
    {
        return $this->createUrl(parent::base($path));
    }

    /**
     * Alias to base()
     * @param string $path
     * @return Url
     */
    public function to($path = null)
    {
        return $this->base($path);
    }

    /**
     * Create url by route name.
     * @param string $routeName
     * @param array $data
     * @param mixed $query (uri query string)
     *
     * @return Url
     * @throws \InvalidArgumentException
     */
    public function create($routeName, array $data = array(), array $query = array())
    {
        return $this->createUrl(parent::create($routeName, $data, $query));
    }

    /**
     * Alias to create()
     * @param string $routeName
     * @param array $data
     * @param array $query
     * @return Url
     */
    public function route($routeName, array $data = array(), array $query = array())
    {
        return $this->create($routeName, $data, $query);
    }

    /**
     * @return Url
     */
    public function parent()
    {
        return $this->createUrl(parent::parent());
    }

    /**
     * Get current url
     * @param array $query query
     * @return Url
     * @throws \Exedra\Exception\InvalidArgumentException
     */
    public function current(array $query = array())
    {
        return $this->createUrl(parent::current($query));
    }
}