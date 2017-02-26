<?php
namespace Exedra\Url;

/**
 * A route oriented url generator
 */
class UrlFactory extends UrlGenerator
{
    /**
     * Get previous url (referer)
     * @return Url|false
     * @throws \Exedra\Exception\NotFoundException
     */
    public function previous()
    {
        $url = parent::previous();

        return $url ? new Url($url) : false;
    }

    /**
     * @param $name
     * @param array $args
     * @return Url
     */
    public function __call($name, array $args = array())
    {
        return new Url(parent::__call($name, $args));
    }

    /**
     * Get url prefixed with $baseUrl
     * @param string $path (optional)
     * @return Url
     */
    public function base($path = null)
    {
        return new Url(parent::base($path));
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
     * Get asset url prefixed with $assetUrl
     * @param string $asset path (optonal)
     * @return Url
     */
    public function asset($asset = null)
    {
        return new Url(parent::asset($asset));
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
        return new Url(parent::create($routeName, $data, $query));
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
        return new Url(parent::parent());
    }

    /**
     * Get current url
     * @param array $query query
     * @return Url
     * @throws \Exedra\Exception\InvalidArgumentException
     */
    public function current(array $query = array())
    {
        return new Url(parent::current($query));
    }
}