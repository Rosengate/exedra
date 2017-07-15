<?php
namespace Exedra\View;

use Exedra\Exception\NotFoundException;
use Exedra\Path;

/**
 * Exedra View Factory
 * @param Path path
 */
class ViewFactory
{
    /**
     * Path to View directory
     * @var Path $path
     */
    protected $path;

    /**
     * Default datas for created views
     * @var array $defaultData
     */
    protected $defaultData = array();

    /**
     * Cached views
     * @var array $views
     */
    protected $views = array();

    public function __construct(Path $path)
    {
        $this->path = $path;
    }

    /**
     * Create view instance based on given relative path.
     * @param string $path
     * @param string|array $data (array to be deprecated)
     * @return View
     * @throws NotFoundException
     */
    public function create($path, $data = array())
    {
        $path = $this->buildPath($path);

        if(!file_exists($path))
            throw new NotFoundException('Unable to find view ['.$path.']');

        // merge with default data.
        $class = '\Exedra\View\View';

        if(is_string($data))
        {
            // assume data as fully qualified class name
            if($data)
            {
                $class = $data;

                $data = array();
            }
        }
        else
        {
            if(!is_array($data))
                throw new NotFoundException('Argument 2 must be either string or array ['.gettype($data).'] given.');
        }

        $data = array_merge($data, $this->defaultData);

        return new $class($path, $data);
    }

    /**
     * Absolutely build path based on the relative one
     * @param string $path
     * @return string
     */
    protected function buildPath($path)
    {
        return $this->path->to(ltrim($path, '/\\') . '.php');
    }

    /**
     * Check file's path existence.
     * @param string $path
     * @return boolean
     */
    public function has($path)
    {
        return file_exists($this->buildPath($path));
    }

    /**
     * Set default data for every view created through this factory.
     * @param array|string $key
     * @param mixed $val
     * @return $this
     */
    public function setDefaultData($key, $val = null)
    {
        if(is_array($key))
        {
            foreach($key as $k=>$v)
            {
                $this->setDefaultData($k, $v);
            }
        }
        else
        {
            $this->defaultData[$key] = $val;
        }

        return $this;
    }

    public function offsetGet($path)
    {
        if(isset($this->views[$path]))
            return $this->views[$path];

        return $this->views[$path] = $this->create($path);
    }
}