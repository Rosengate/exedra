<?php
class HandlerTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->app = new \Exedra\Application(__DIR__);

		$this->app->autoloadSrc();
	}

	public function testFunctionalHandler()
	{
		$this->app->map->addExecuteHandler('foo', function($handler)
		{
			$handler->onValidate(function($pattern)
			{
				if(strpos($pattern, 'foo@') === 0)
					return true;

				return false;
			});

			$handler->onResolve(function($pattern)
			{
				$essence = substr($pattern, 4);

				return function($exe) use($essence)
				{
					return $essence;
				};
			});
		});

		$this->app->map['foo']->any('/')->execute('foo@bar');

		$this->assertEquals('bar', $this->app->execute('foo')->response->getBody());
	}

	public function testClassHandler()
	{
		$this->app->map->addExecuteHandler('bar', \App\Handlers\FooHandler::class);

		$this->app->map['bar']->any('/')->execute('bar=baz');

		$this->assertEquals('bar=baz', $this->app->execute('bar')->response->getBody());
	}
}