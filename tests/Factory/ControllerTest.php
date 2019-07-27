<?php
class FactoryControllerTest extends \BaseTestCase
{
	public function caseSetUp()
	{
		$app = new \Exedra\Application(__DIR__.'/Factory');

        $app->provider->add(\Exedra\Support\Provider\Framework::class);

        $app->config['namespace'] = 'App';

		$this->app = $app;
	}

	public function testController()
	{
		$context = $this;

		$this->app->map['fooRoute']->any('/[:controller?]')->execute(function($exe)
		{
			return $exe->controller->execute(array($exe->param('controller', 'Foo'), array($exe)), 'bar');
		});

		$this->app->map['barRoute']->any('/[:controller?]')->execute('controller=Foo@bar');

		$this->app->map['bazRoute']->any('/[:controller?]')->execute(function($exe)
		{
			return $exe->controller->create('Foo', array($exe));
		});

		$this->assertEquals('baz', $this->app->execute('fooRoute')->response->getBody());

		$this->assertEquals('baz', $this->app->execute('barRoute')->response->getBody());

		$this->assertEquals(\App\Controller\Foo::CLASS, get_class($this->app->execute('bazRoute')->response->getBody()));
	}
}

