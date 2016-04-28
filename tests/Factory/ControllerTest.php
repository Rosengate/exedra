<?php
class FactoryControllerTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$app = new \Exedra\Application(__DIR__.'/TestApp');

		$this->app = $app;
	}

	public function testController()
	{
		$context = $this;

		$this->app->map->any('/[:controller?]')->name('fooRoute')->execute(function($exe)
		{
			return $exe->controller->execute(array($exe->param('controller', 'Foo'), array($exe)), 'bar');
		});

		$this->app->map->any('/[:controller?]')->name('barRoute')->execute('controller=Foo@bar');

		$this->app->map->any('/[:controller?]')->name('bazRoute')->execute(function($exe)
		{
			return $exe->controller->create('Foo', array($exe));
		});

		$this->assertEquals('baz', $this->app->execute('fooRoute')->response->getBody());

		$this->assertEquals('baz', $this->app->execute('barRoute')->response->getBody());

		$this->assertEquals(\TestApp\Controller\Foo::CLASS, get_class($this->app->execute('bazRoute')->response->getBody()));
	}
}

