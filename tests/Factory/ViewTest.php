<?php
class FactoryViewTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$app = new \Exedra\Application(__DIR__);

        $app->provider->add(\Exedra\Support\Provider\Framework::class);

		$this->viewFactory = new \Exedra\View\Factory($app->path['app']->create('views'));
	}

	public function viewCreate()
	{
		return $this->viewFactory->create('TestView');
	}

	public function testCreate()
	{
		$view = $this->viewCreate();

		$this->assertTrue($view instanceof \Exedra\View\View);
	}

	public function testRender()
	{
		$this->assertEquals($this->viewCreate()->render(), 'Test View Content');
	}

	public function testRenderWithData()
	{
		$view = $this->viewCreate();

		$view->set('testData', 'testDataValue');

		$this->assertEquals($view->render(), 'Test View Content'.'testDataValue');
	}

	public function testRenderWithDefaultData()
	{
		$this->viewFactory->setDefaultData('testDefaultData', 'testDefaultDataValue');

		$view = $this->viewCreate();

		$this->assertEquals($view->render(), 'Test View Content'.'testDefaultDataValue');
	}

	public function testRenderWithRequiredData()
	{
		$view = $this->viewCreate();

		$view->setRequiredData('title');
		
		$exceptionThrown = false;

		try
		{
			$view->render();
		}
		catch(\Exedra\Exception\InvalidArgumentException $e)
		{
			$exceptionThrown = true;
		}

		$this->assertEquals($exceptionThrown, true);

		$view->set('title', 'hello');

		$this->assertEquals($view->render(), 'Test View Content');
	}
}