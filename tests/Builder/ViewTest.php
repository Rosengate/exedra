<?php

require_once "Exedra/Exedra.php";

class BuilderViewTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$exedra = new \Exedra\Exedra(__DIR__);

		$app = $exedra->build('TestApp');

		$this->builderView = new \Exedra\Application\Builder\View(new \Exedra\Application\Builder\Exception, $app->loader);
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
		ob_start();
		$this->viewCreate()->render();
		$content = ob_get_clean();

		$this->assertEquals($content, 'Test View Content');
	}

	public function testRenderWithData()
	{
		$view = $this->viewCreate();

		$view->set('testData', 'testDataValue');

		ob_start();
		$view->render();
		$content = ob_get_clean();

		$this->assertEquals($content, 'Test View Content'.'testDataValue');
	}

	public function testRenderWithDefaultData()
	{
		$this->builderView->setDefaultData('testDefaultData', 'testDefaultDataValue');

		$view = $this->viewCreate();

		ob_start();
		$view->render();
		$content = ob_get_clean();

		$this->assertEquals($content, 'Test View Content'.'testDefaultDataValue');
	}

	public function testRenderWithRequiredData()
	{
		$view = $this->viewCreate();

		$view->setRequiredData('title');
		
		$exceptionThrown = false;

		try
		{
			ob_start();
			$view->render();
			$content = ob_get_clean();
		}
		catch(\Exedra\Application\Exception\Exception $e)
		{
			$exceptionThrown = true;
		}

		$this->assertEquals($exceptionThrown, true);

		$view->set('title', 'hello');

		ob_start();
		$view->render();
		$content = ob_get_clean();

		$this->assertEquals($content, 'Test View Content');
	}
}