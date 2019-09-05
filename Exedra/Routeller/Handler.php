<?php

namespace Exedra\Routeller;

use Exedra\Application;
use Exedra\Contracts\Routing\GroupHandler;
use Exedra\Exception\Exception;
use Exedra\Routing\Factory;
use Exedra\Routing\Group;
use Exedra\Routing\Route;
use Exedra\Routeller\Cache\CacheInterface;
use Exedra\Routeller\Cache\EmptyCache;
use Exedra\Routeller\Controller\Controller;
use Minime\Annotations\Cache\ArrayCache;

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

    protected $isAutoReload;

    /**
     * @var Application|null
     */
    protected $app;

    public function __construct(Application $app = null, CacheInterface $cache = null, array $options = array())
    {
        $this->app = $app;

        $this->cache = $cache ? $cache : new EmptyCache;

        $this->options = $options;

        $this->isAutoReload = isset($this->options['auto_reload']) && $this->options['auto_reload'] === true ? true : false;
    }

    public function validateGroup($pattern, Route $route = null)
    {
        if (is_string($pattern)) {
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

    protected function createReader()
    {
        return new AnnotationsReader(new AnnotationsParser(), new ArrayCache());
    }

    /**
     * @param Factory $factory
     * @param $routing
     * @param Route|null $parentRoute
     * @return \Exedra\Routing\Group
     * @throws Exception
     */
    public function resolveGroup(Factory $factory, $controller, Route $parentRoute = null)
    {
        $group = $factory->createGroup(array(), $parentRoute);

        if (is_object($controller)) {
            $classname = get_class($controller);
        } else {
            if (strpos($controller, 'routeller=') === 0) {
                list($classname, $method) = explode('@', str_replace('routeller=', '', $controller));

                $controller = $classname::instance()->{$method}($this->app);

                if (!$this->validateGroup($controller))
                    throw new Exception('Unable to validate the routing group for [' . $classname . '::' . $method . '()]');

                return $this->resolveGroup($factory, $controller, $parentRoute);
            } else if (strpos($controller, 'routeller_call') === 0) {
                list($classname, $method) = explode('@', str_replace('routeller_call=', '', $controller));

                $classname::instance()->{$method}($group, $this->app);

                return $group;
            }

            $classname = $controller;

            /** @var Controller $controller */
            $controller = $controller::instance();
        }

        $key = md5(get_class($controller));

        $entries = null;

        if ($this->isAutoReload) {
            $reflection = new \ReflectionClass($controller);

            $lastModified = filemtime($reflection->getFileName());

            $cache = $this->cache->get($key);

            if ($cache) {
                if ($cache['last_modified'] != $lastModified) {
                    $this->cache->clear($key);
                } else {
                    $entries = $cache['entries'];
                }
            }
        } else {
            $cache = $this->cache->get($key);

            if ($cache)
                $entries = $cache['entries'];
        }

        if ($entries) {
            foreach ($entries as $entry) {
                if (isset($entry['middleware'])) {
                    $group->addMiddleware(function () use ($controller, $entry) {
                        return call_user_func_array(array($controller, $entry['middleware']['handle']), func_get_args());
                    }, $entry['middleware']['properties']);
                } else if (isset($entry['route'])) {
                    $properties = $entry['route']['properties'];

                    $group->addRoute($route = $factory->createRoute($group, isset($properties['name']) ? $properties['name'] : $entry['route']['name'], $properties));

                    if (isset($entry['route']['route_call'])) {
                        list($classname, $methodName) = explode('@', $entry['route']['route_call']);

                        $classname::instance()->{$methodName}($route, $this->app);
                    }
                } else if (isset($entry['setup'])) {
                    $controller::instance()->{$entry['setup']['method']}($group, $this->app);
                }
            }

            return $group;
        }

        if (!$this->isAutoReload)
            $reflection = new \ReflectionClass($controller);

        if (!$reflection->isSubclassOf(Controller::class))
            throw new Exception('[' . $classname . '] must be a type of [' . Controller::class . ']');

        $reader = $this->createReader();

        $entries = array();

        // loop all the class's methods
        foreach ($reflection->getMethods() as $reflectionMethod) {
            $methodName = $reflectionMethod->getName();

            if (strpos($methodName, 'middleware') === 0) {
                $properties = $reader->getRouteProperties($reflectionMethod);

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
                $controller->{$methodName}($group, $this->app);

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

            $properties = $reader->getRouteProperties($reflectionMethod);

            // read from route properties from the class itself
            if ($type == 'subroutes' && isset($properties['deferred'])) {
                $cname = $controller->{$methodName}($this->app);
                $controllerRef = new \ReflectionClass($controller->{$methodName}($this->app));

                if (!$controllerRef->isSubclassOf(Controller::class))
                    throw new Exception('[' . $cname . '] must be a type of [' . Controller::class . ']');

                $properties = $reader->getRouteProperties($controllerRef);
            }

            if ($method && !isset($properties['method']))
                $properties['method'] = $method;

            if (count($properties) == 0)
                continue;

            if ($type == 'execute') { // if it is, save the closure.
                $properties['execute'] = 'routeller=' . $classname . '@' . $reflectionMethod->getName();
            } else if ($type == 'subroutes') {
                $properties['subroutes'] = $controller->{$methodName}($this->app);
            }

            if (isset($properties['name']))
                $properties['name'] = (string)$properties['name'];

            if (isset($properties['inject']) && is_string($properties['inject']))
                $properties['inject'] = array_map('trim', explode(',', trim($properties['inject'], ' []')));

            $group->addRoute($route = $factory->createRoute($group, $routeName = (isset($properties['name']) ? $properties['name'] : $routeName), $properties));

            $entry = array(
                'route' => array(
                    'name' => $routeName,
                    'properties' => $properties
                )
            );

            if ($type == 'subroutes_call') {
                $app = $this->app;

                $route->group(function (Group $group) use ($controller, $methodName, $app) {
                    $controller->{$methodName}($group);
                });

                $entry['route']['properties']['subroutes'] = 'routeller_call=' . $classname . '@' . $methodName;
            } else if (isset($properties['subroutes'])) {
                $entry['route']['properties']['subroutes'] = 'routeller=' . $classname . '@' . $methodName;
            } else if ($type == 'route_call') {
                $controller->{$methodName}($route, $this->app);
                $entry['route']['route_call'] = $classname . '@' . $methodName;
            }

            $entries[] = $entry;
        }

        $this->cache->set($key, $entries, isset($lastModified) ? $lastModified : filemtime($reflection->getFileName()));

        return $group;
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
