<?php

require_once "Exedra/Exedra.php";

class BuilderViewTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$exedra = new \Exedra\Exedra(__DIR__);

		$app = $exedra->build('TestApp');

		$this->builderView = new \Exedra\Application\Builder\View(new \Exedra\Application\Builder\Exception($app), $app->loader);
	}

	public function viewCreate()
	{
		return $this->builderView->create('TestView');
	}

	public function testCreate()
	{
		$view = $this->viewCreate();

		$this->assertEquals($view instanceof \Exedra\Application\Builder\Blueprint\View, true);
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
		$this->builderView->setDefaultData('testDefaultData', 'testDefaultDataValue');

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
		catch(\Exedra\Application\Exception\Exception $e)
		{
			$exceptionThrown = true;
		}

		$this->assertEquals($exceptionThrown, true);

		$view->set('title', 'hello');

		$this->assertEquals($view->render(), 'Test View Content');
	}
}