<?php

namespace Exedra\Routeller;

use Exedra\Application;
use Exedra\Contracts\Routing\GroupHandler;
use Exedra\Exception\Exception;
use Exedra\Exception\InvalidArgumentException;
use Exedra\Routeller\Contracts\PropertyResolver;
use Exedra\Routeller\Contracts\RoutePropertiesReader;
use Exedra\Routeller\PropertyResolvers\ConfigResolver;
use Exedra\Routing\Factory;
use Exedra\Routing\Group;
use Exedra\Routing\Route;
use Exedra\Routeller\Cache\CacheInterface;
use Exedra\Routeller\Cache\EmptyCache;
use Exedra\Routeller\Controller\Controller;
use Psr\Container\ContainerInterface;

class Handler implements GroupHandler
{
    /**
     * @var array $httpVerbs
     */
    protected static $httpVerbs = array('get', 'post', 'put', 'patch', 'delete', 'options');

    /**
     * @var
     */
    protected $caches;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var bool
     */
    protected $isAutoReload;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var \ReflectionClass[]
     */
    protected $classesReflection = [];

    /**
     * @var PropertyResolver[]
     */
    protected $propertyResolvers;

    /**
     * @var array
     */
    protected $refClassProperties = [];

    public function __construct(ContainerInterface $container = null,
                                array $propertyResolvers = array(),
                                CacheInterface $cache = null,
                                array $options = array())
    {
        $this->container = $container;

        $this->propertyResolvers = $propertyResolvers;

        $this->cache = $cache ? $cache : new EmptyCache;

        $this->options = $options;

        $this->isAutoReload = isset($this->options['auto_reload']) && $this->options['auto_reload'] === true ? true : false;
    }

    /**
     * Just a factory to help create handler for exedra app
     *
     * @param Application $app
     * @param CacheInterface|null $cache
     * @param array $options
     * @return static
     */
    public static function createAppHandler(Application $app, CacheInterface $cache = null, array $options = array())
    {
        return new static($app, array(new ConfigResolver($app->config)), $cache, $options);
    }

    public function validateGroup($pattern, Route $route = null)
    {
        if (is_string($pattern)) {
            if (strpos($pattern, 'routeller_class') === 0)
                return true;

            if (strpos($pattern, 'routeller=') === 0)
                return true;

            if (strpos($pattern, 'routeller_call=') === 0)
                return true;

            if (class_exists($pattern))
                return true;
        }

        if (is_object($pattern) && $pattern instanceof Controller)
            return true;

        return false;
    }

    /**
     * @return RoutePropertiesReader
     */
    protected function createReader()
    {
        if (isset($this->options['reader']))
            return $this->options['reader'];
        else
            return new AnnotationsReader(isset($this->options['property_parsers']) ? $this->options['property_parsers'] : []);
    }

    public function readReflectionClassProperties(\ReflectionClass $refClass, RoutePropertiesReader $reader)
    {
        if (isset($this->refClassProperties[$name = $refClass->getName()])) {
            return $this->refClassProperties[$name];
        }

        return $this->refClassProperties[$name] = $reader->readProperties($refClass);
    }

    /**
     * @param $className
     * @return \ReflectionClass
     */
    protected function getClassReflection($className)
    {
        if (isset($this->classesReflection[$className]))
            return $this->classesReflection[$className];

        return $this->classesReflection[$className] = new \ReflectionClass($className);
    }

    /**
     * @param Factory $factory
     * @param $routing
     * @param Route|null $parentRoute
     * @return \Exedra\Routing\Group
     * @throws Exception
     */
    public function resolveGroup(Factory $factory, $pattern, Route $parentRoute = null)
    {
        $group = $factory->createGroup(array(), $parentRoute);

        if (is_object($pattern)) {
//            $classname = get_class($pattern);

            $controller = $pattern;
        } else {
            // resolve pattern
            if (strpos($pattern, 'routeller_class=') === 0) {
                return $this->resolveGroup($factory, str_replace('routeller_class=', '', $pattern), $parentRoute);
            // pattern is uncertain
            } else if (strpos($pattern, 'routeller=') === 0) {
                list($classname, $method) = explode('@', str_replace('routeller=', '', $pattern));

                $controller = $classname::instance()->{$method}($this->container);

                if (!$this->validateGroup($controller))
                    throw new Exception('Unable to validate the routing group for [' . $classname . '::' . $method . '()]');

                return $this->resolveGroup($factory, $controller, $parentRoute);

            // for sub prefix
            } else if (strpos($pattern, 'routeller_call') === 0) {
                list($classname, $method) = explode('@', str_replace('routeller_call=', '', $pattern));

                $classname::instance()->{$method}($group, $this->container);

                return $group;
            }

            $classname = $pattern;

            /** @var Controller $controller */
            $controller = $classname::instance();
        }

        $key = md5(get_class($controller));

        $entries = null;

        if ($this->isAutoReload) {
            $reflection = $this->getClassReflection(get_class($controller));

            $lastModified = filemtime($reflection->getFileName());

            $cache = $this->cache->get($key);

            if ($cache) {
                if ($cache['last_modified'] != $lastModified) {
                    $this->cache->clear($key);
                } else {
                    $entries = $cache['entries'];

                    // check for deferred routing cache
                    foreach ($entries as $entry) {
                        if (!isset($entry['route']))
                            continue;

                        if (isset($entry['route']['properties']) &&
                            isset($entry['route']['properties']['subroutes']) &&
                            is_string($entry['route']['properties']['subroutes']) && strpos($entry['route']['properties']['subroutes'], 'routeller_class=') === 0) {

                            @list($subroutesClass) = explode('@', str_replace('routeller_class=', '', $entry['route']['properties']['subroutes']));
                            $subroutesKey = md5($subroutesClass);
                            $subroutesCache = $this->cache->get($subroutesKey);

                            $ref = $this->getClassReflection($subroutesClass);

                            // check cache for subroutes
                            if (!$subroutesCache || ($subroutesCache && $subroutesCache['last_modified'] != filemtime($ref->getFileName()))) {
                                $entries = null;
                                $this->cache->clear($key);
                                $this->cache->clear($subroutesKey);
                                break;
                            }
                        }
                    }
                }
            }
        } else {
            $cache = $this->cache->get($key);

            if ($cache)
                $entries = $cache['entries'];
        }

        // cache entries handling
        if ($entries) {
            foreach ($entries as $entry) {
                if (isset($entry['middleware'])) {
                    $group->addMiddleware(function () use ($controller, $entry) {
                        return call_user_func_array(array($controller, $entry['middleware']['handle']), func_get_args());
                    }, $entry['middleware']['properties']);
                } else if (isset($entry['route'])) {
//                    $properties = $entry['route']['properties'];

                    $merges = $this->resolveProperties($entry['route']['properties']);

                    $group->addRoute($route = $factory->createRoute($group, isset($merges['name']) ? $merges['name'] : $entry['route']['name'], $merges));

                    if (isset($entry['route']['route_call'])) {
                        list($classname, $methodName) = explode('@', $entry['route']['route_call']);

                        $classname::instance()->{$methodName}($route, $this->container);
                    }
                } else if (isset($entry['setup'])) {
                    $controller::instance()->{$entry['setup']['method']}($group, $this->container);
                }
            }

            return $group;
        }

        if (!$this->isAutoReload) {
            $reflection = $this->getClassReflection(get_class($controller));
        }

        if (isset($reflection) && !$reflection->isSubclassOf(Controller::class))
            throw new Exception('[' . $classname . '] must be a type of [' . Controller::class . ']');

        $reader = $this->createReader();

        if ($parentRoute) {
            $parentRoute->setProperties($this->readReflectionClassProperties($reflection, $reader));
        }

        $entries = array();

        // loop all the refClass's methods
        foreach ($reflection->getMethods() as $reflectionMethod) {
            $methodName = $reflectionMethod->getName();

            if (strpos($methodName, 'middleware') === 0) {
                $properties = $reader->readProperties($reflectionMethod);

                $entries[] = array(
                    'middleware' => array(
                        'properties' => $properties,
                        'handle' => $reflectionMethod->getName()
                    )
                );

                if (isset($properties['inject'])) {
                    $properties['dependencies'] = $properties['inject'];
                    unset($properties['inject']);
                }

                if (isset($properties['dependencies']) && is_string($properties['dependencies']))
                    $properties['dependencies'] = array_map('trim', explode(',', trim($properties['dependencies'], ' []')));

                $group->addMiddleware($reflectionMethod->getClosure($controller), $properties);

                continue;
            }

            if (strpos(strtolower($methodName), 'setup') === 0) {
                $controller->{$methodName}($group, $this->container);

                $entries[] = array(
                    'setup' => array(
                        'method' => $methodName
                    )
                );

                continue;
            }

            $type = null;

            $method = null;

            if ($routeName = $this->parseExecuteMethod($methodName)) {
                $type = 'execute';
            } else if ($routeName = $this->parseGroupMethod($methodName)) {
                $type = 'subroutes';
            } else if ($result = $this->parseRestfulMethod($methodName)) {
                $type = 'execute';

                @list($routeName, $method) = $result;
            } else if ($routeName = $this->parseSubMethod($methodName)) {
                $type = 'subroutes_call';
            } else if ($routeName = $this->parseRouteMethod($methodName)) {
                $type = 'route_call';
            } else {
                continue;
            }

            $properties = $reader->readProperties($reflectionMethod);

            // read from route properties from the refClass itself
            $subrouteClass = null;

            if ($type == 'subroutes') {
                $cname = $controller->{$methodName}($this->container);

                if ($this->validateGroup($cname)) {
                    $controllerRef = $this->getClassReflection($controller->{$methodName}($this->container));

                    if (!$controllerRef->isSubclassOf(Controller::class))
                        throw new Exception('[' . $cname . '] must be a type of [' . Controller::class . ']');

                    // read controller route properties
                    $properties = $this->propertiesDeferringMerge($this->readReflectionClassProperties($controllerRef, $reader), $properties);

                    $subrouteClass = $cname;
                }
            }

            if ($method && !isset($properties['method']))
                $properties['method'] = $method;

            if (count($properties) == 0)
                continue;

            if ($type == 'execute') { // if it is, save the closure.
                $properties['execute'] = 'routeller=' . $classname . '@' . $reflectionMethod->getName();
            } else if ($type == 'subroutes') {
                $properties['subroutes'] = $controller->{$methodName}($this->container);
            }

            if (isset($properties['name']))
                $properties['name'] = (string)$properties['name'];

            if (isset($properties['inject']) && is_string($properties['inject']))
                $properties['inject'] = array_map('trim', explode(',', trim($properties['inject'], ' []')));

            $merges = $this->resolveProperties($properties);

            $group->addRoute($route = $factory->createRoute($group, $routeName = (isset($merges['name']) ? $merges['name'] : $routeName), $merges));

            // caching preparation
            $entry = array(
                'route' => array(
                    'name' => $routeName,
                    'properties' => $properties
                )
            );

            if ($type == 'subroutes_call') {
                $app = $this->container;

                $route->group(function (Group $group) use ($controller, $methodName, $app) {
                    $controller->{$methodName}($group);
                });

                $entry['route']['properties']['subroutes'] = 'routeller_call=' . $classname . '@' . $methodName;
            } else if (isset($properties['subroutes'])) {
                if ($subrouteClass)
                    $entry['route']['properties']['subroutes'] = 'routeller_class=' . $subrouteClass;
                else
                    $entry['route']['properties']['subroutes'] = 'routeller=' . $classname . '@' . $methodName;

            } else if ($type == 'route_call') {
                $controller->{$methodName}($route, $this->container);
                $entry['route']['route_call'] = $classname . '@' . $methodName;
            }

            $entries[] = $entry;
        }

        $this->cache->set($key, $entries, isset($lastModified) ? $lastModified : filemtime($reflection->getFileName()));

        return $group;
    }

    protected function resolveProperties(array $properties)
    {
        $merges = $properties;

        foreach ($properties as $key => $value) {
            foreach ($this->propertyResolvers as $resolver)
                $merges[$key] = $resolver->resolve($key, $value);
        }

        return $merges;
    }

    /**
     * Merge properties for deferred routing
     *
     * @param array $properties
     * @param array $controllerProperties
     * @return array
     */
    public function propertiesDeferringMerge(array $controllerProperties, array $properties)
    {
        $merges = array_merge($properties, $controllerProperties);

        if (isset($controllerProperties['path']) && isset($properties['path']))
            $merges['path'] = '/'. trim($properties['path'], '/') . '/'. trim($controllerProperties['path'], '/');

        if (isset($properties['attr']) && isset($controllerProperties['attr'])) {
            $merges['attr'] = $properties['attr'];

            foreach ($controllerProperties['attr'] as $key => $value) {
                if (is_array($value)) {
                    if (isset($merges['attr'][$key]) && !is_array($merges['attr'][$key]))
                        throw new InvalidArgumentException('Unable to push value into attribute [' . $key . '].');

                    foreach ($value as $val) {
                        $merges['attr'][$key][] = $val;
                    }
                } else {
                    $merges['attr'][$key] = $value;
                }
            }
        }

        return $merges;
    }

    /**
     * @param $method
     * @return null|string
     */
    public function parseRouteMethod($method)
    {
        if (strpos($method, 'route') !== 0)
            return null;

        return strtolower(substr($method, 5, strlen($method)));
    }

    /**
     * @param string $method
     * @return null|string
     */
    public function parseSubMethod($method)
    {
        if (strpos($method, 'sub') !== 0)
            return null;

        return strtolower(substr($method, 3, strlen($method)));
    }

    /**
     * get route name and the verb if it's prefixed with one of the http verbs.
     * @param $method
     * @return array|null
     */
    public function parseRestfulMethod($method)
    {
        foreach (static::$httpVerbs as $verb) {
            if (strpos($method, $verb) === 0) {
                $methodName = strtolower(substr($method, strlen($verb), strlen($method)));
                $routeName = $methodName ? $verb . '-' . $methodName : $verb;
                $method = $verb;

                return array($routeName, $method);
            }
        }

        return null;
    }

    /**
     * Get route name if it's prefixed with 'execute'
     * @return string|null
     */
    protected function parseExecuteMethod($method)
    {
        if (strpos($method, 'execute') !== 0)
            return null;

        return strtolower(substr($method, 7, strlen($method)));
    }

    /**
     * Get route name if it's prefixed with 'group'
     * @return string|null
     */
    protected function parseGroupMethod($method)
    {
        if (strpos($method, 'group') !== 0)
            return null;

        return strtolower(substr($method, 5, strlen($method)));
    }
}
