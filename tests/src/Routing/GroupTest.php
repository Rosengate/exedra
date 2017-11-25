<?php
class GroupTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Exedra\Routing\Group $rootGroup
     */
    protected $rootGroup;

    protected $factory;

    public function setUp()
    {
        $this->factory = new \Exedra\Routing\Factory;

        $this->rootGroup = $this->factory->createGroup(array());
    }

    public function createRequest(array $params)
    {
        return \Exedra\Http\ServerRequest::createFromArray($params);
    }

    public function createNestedRoute($name = null, $properties = array())
    {
        return $this->rootGroup['foo']->any('/foo')->group(function(\Exedra\Routing\Group $group)
        {
            $group['bar']->any('/bar')->execute(function(){ });

            $group['baz']->any('/:baz')->execute(function(){});
        });
    }

    public function testFailRoute()
    {
        $this->createNestedRoute();

        $request = $this->createRequest(['uri' => ['path' => '/foo/basr']]);

        $this->rootGroup->setFailRoute('foo.bar');

        $this->assertEquals('foo.bar', $this->rootGroup->getFailRoute());

        $this->assertInstanceOf(\Exedra\Routing\Finding::class, $this->rootGroup->findByRequest($request));
    }
}