<?php
class RouteTest extends \BaseTestCase
{
    /** @var \Exedra\Routing\Factory */
    protected $factory;

    /** @var \Exedra\Routing\Group $rootGroup */
    protected $rootGroup;

    public function caseSetUp()
    {
        $this->factory = new \Exedra\Routing\Factory();

        $this->rootGroup = $this->factory->createGroup(array());
    }

    public function createNestedRoute($name = null, $properties = array())
    {
        return $this->rootGroup['foo']->any('/foo')->group(function(\Exedra\Routing\Group $group)
        {
            $group['bar']->any('/bar')->execute(function(){});

            $group['baz']->any('/:baz')->execute(function(){});
        });
    }

    public function testGetName()
    {
        $route = $this->createNestedRoute('foo');

        $this->assertEquals('foo', $route->getName());
    }

    public function testGetGroup()
    {
        $this->assertEquals($this->rootGroup, $this->createNestedRoute()->getGroup());
    }

    public function testGetAbsoluteName()
    {
        $route = $this->createNestedRoute();

        $this->assertEquals('foo', $route->getAbsoluteName());

        $this->assertEquals('foo.bar', $this->rootGroup->findRoute('foo.bar')->getAbsoluteName());
    }

    public function testGetAbsolutePath()
    {
        $route = $this->createNestedRoute();

        $this->assertEquals('foo', $route->getAbsolutePath());

        $this->assertEquals('foo/bar', $this->rootGroup->findRoute('foo.bar')->getAbsolutePath());

        $this->assertEquals('foo/boo', $this->rootGroup->findRoute('foo.baz')->getAbsolutePath(array('baz' => 'boo')));
    }

    public function testGetPath()
    {
        $route = $this->rootGroup['foo']->any('/foo')->group(function(\Exedra\Routing\Group $group)
        {
            $group['bar']->any('/bar')->execute(function(){});
        });

        $this->assertEquals('foo', $route->getPath());
    }

    public function testGetFullRoutes()
    {
        $route = $this->rootGroup['foo']->any('/foo')->group(function(\Exedra\Routing\Group $group)
        {
            $group['bar']->any('/bar')->execute(function(){});
        });

        $this->assertTrue(is_array($routes = $this->rootGroup->findRoute('foo.bar')->getFullRoutes()));

        $this->assertCount(2, $routes);

        $this->assertEquals($this->rootGroup->findRoute('foo'), $routes[0]);

        $this->assertEquals($this->rootGroup->findRoute('foo.bar'), $routes[1]);
    }

    public function testGetFailRouteName()
    {
        $route = $this->createNestedRoute();

        $route->setAsFailRoute();

        $this->assertEquals($this->rootGroup->findRoute('foo.bar')->getFailRouteName(), 'foo');
    }

    public function testGetParentRouteName()
    {
        $route = $this->createNestedRoute();

        $this->assertEquals($route->getName(), $this->rootGroup->findRoute('foo.bar')->getParentRouteName());
    }

    public function testGetParameterizedPath()
    {
        $this->createNestedRoute();

        $this->assertEquals('boo', $this->rootGroup->findRoute('foo.baz')->getParameterizedPath(array('baz' => 'boo')));
    }

    public function testHasSubroutes()
    {
        $route = $this->createNestedRoute();

        $this->assertTrue($route->hasSubroutes());
    }

    public function testGetMethod()
    {
        $route = $this->createNestedRoute();

        $route->setMethod('GET');

        $this->assertEquals($route->getMethod(), array('get'));

        $route->setMethod('POST|PUT');

        $this->assertEquals($route->getMethod(), array('post', 'put'));

        $route->setMethod(array('GET', 'POST', 'PATCH'));

        $this->assertEquals($route->getMethod(), array('get', 'post', 'patch'));
    }

    public function testHasExecution()
    {
        $route = $this->createNestedRoute();

        $this->assertFalse($route->hasExecution());

        $this->assertTrue($this->rootGroup->findRoute('foo.bar')->hasExecution());
    }

    public function testSetPath()
    {
        $route = $this->createNestedRoute();

        $route->setPath('baz/baz');

        $this->assertEquals($route->getPath(), 'baz/baz');
    }

    public function testSetConfig()
    {
        $route = $this->createNestedRoute();

        $route->setConfig(array('foo' => 'bar'));

        $this->assertEquals(array('foo' => 'bar'), $route->getProperty('config'));
    }
}