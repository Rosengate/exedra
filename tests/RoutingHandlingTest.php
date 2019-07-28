<?php

class RoutingHandlingTest extends BaseTestCase
{
    /**
     * @var \Exedra\Application
     */
    protected $app;

    public function caseSetUp()
    {
        $app = $this->app = new \Exedra\Application(__DIR__.'/Factory');
    }

    public function request($path, $method = 'GET')
    {
        return $this->app->request(\Exedra\Http\ServerRequest::createFromArray(['uri' => ['path' => $path], 'method' => $method]));
    }

    public function testRoutingHandler()
    {
        $this->app->routingFactory->addRoutingHandler(new \App\MyRoutingHandler());

        $this->app->map['web']->group('paths=index,contact-us,faq');

        $this->assertEquals('index', $this->request('index')->response->getBody());
        $this->assertEquals('contact-us', $this->request('contact-us')->response->getBody());
        $this->assertEquals('faq', $this->request('faq')->response->getBody());
    }


}