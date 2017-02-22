<?php
namespace Exedra\Support;

/**
 * A singleton to manage autoloading
 * Class Autoloader
 * @package Exedra\Support
 */
class Autoloader
{
    /**
     * @var Autoloader $instance
     */
    protected static $instance;

    protected $registry = array();

    protected function __construct()
    {
    }

    public static function getInstance()
    {
        if(!static::$instance)
        {
            static::$instance = new static();

            static::$instance->splRegister();
        }

        return static::$instance;
    }

    public function getRegistry()
    {
        return $this->registry;
    }

    protected function splRegister()
    {
        $self = $this;

        spl_autoload_register(function($class) use($self)
        {
            foreach($self->getRegistry() as $args)
            {
                $autoloadPath = $args[0];

                $namespace = $args[1];

                if($namespace != '' && strpos($class, $namespace) !== 0)
                    continue;

                $classDir = substr($class, strlen($namespace));

                $filename = $autoloadPath . DIRECTORY_SEPARATOR . (str_replace('\\', DIRECTORY_SEPARATOR, $classDir)) . '.php';

                if(file_exists($filename))
                    return require_once $filename;
            }
        });
    }

    /**
     * PSR-4 autoloader path register
     * @param string $basePath
     * @param string $namespace (optional), a namespace prefix
     * @return $this
     */
    public function registerAutoload($basePath, $namespace = '')
    {
        $this->registry[] = array($basePath, $namespace);

        return $this;
    }

    /**
     * Alias to registerAutoload
     * @param string $path
     * @param string|null $namespace
     * @return Autoloader
     */
    public function autoload($path, $namespace = '')
    {
        return $this->registerAutoload($path, $namespace);
    }

    /**
     * Psr autoload
     * @param string $namespace
     * @param string $path
     * @return Autoloader
     */
    public function autoloadPsr4($namespace, $path)
    {
        return $this->registerAutoload($path, $namespace);
    }
}