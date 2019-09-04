<?php
class ControllerTest extends BaseTestCase
{
    /**
     * @var \Exedra\Application
     */
    protected $app;
    public function caseSetUp()
    {
        $app = $this->app = new \Exedra\Application(__DIR__.'/Factory');
        $app->provider->add(\Exedra\Routeller\RoutellerProvider::class);
        $app->map['foo']->any('/foo')->group(\Foo\Ctrls\FooController::class);
    }
    public function request($path, $method = 'GET')
    {
        return $this->app->request(\Exedra\Http\ServerRequest::createFromArray(['uri' => ['path' => $path], 'method' => $method]));
    }
    public function testRouting()
    {
        $this->assertEquals('foo.index', $this->request('foo/index')->route->getAbsoluteName());
    }
    public function testImmediateSubrouting()
    {
        $this->assertEquals('foo.bar.baz.bat', $this->request('foo/bar/baz')->route->getAbsoluteName());
    }
    public function testNormalRouting()
    {
        $this->assertEquals('foo.bar.bah', $this->request('foo/bar/bah')->route->getAbsoluteName());
    }
    public function testRest()
    {
        $this->assertEquals('foo.get', $this->request('foo')->route->getAbsoluteName());
        $this->assertEquals('foo.get-users', $this->request('foo/users')->route->getAbsoluteName());
        $this->assertEquals('foo.post', $this->request('foo', 'POST')->route->getAbsoluteName());
        $this->assertEquals('foo.put', $this->request('foo', 'PUT')->route->getAbsoluteName());
        $this->assertEquals('foo.delete', $this->request('foo', 'DELETE')->route->getAbsoluteName());
        $this->assertEquals('foo.patch', $this->request('foo', 'PATCH')->route->getAbsoluteName());
    }
    public function testMiddleware()
    {
        $this->assertEquals('middleware foo hello', $this->request('foo')->response->getBody());
        $this->assertEquals('middleware bar-middleware foobarbaz', $this->request('foo/bar')->response->getBody());
    }
    public function testGrouping()
    {
        $this->assertEquals('foo.bar.hello', $this->request('foo/bar/hello')->route->getAbsoluteName());
        $this->assertEquals('middleware hello world', $this->request('foo/bar/hello')->response->getBody());
    }
    public function testTag()
    {
        $this->assertEquals('foo.bar.get', $this->app->map->findRoute('#holla')->getAbsoluteName());
    }
    public function testAttr()
    {
        $this->assertEquals('middleware bar-middleware foobarbaz', $this->app->execute('#holla')->response->getBody());
    }
    public function testConfig()
    {
        $this->assertEquals('middleware confvalue', $this->app->execute('#testconf')->response->getBody());
    }
}