<?php
class LoaderTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->loader = new \Exedra\Path(__DIR__.'/Factory');
	}

	public function testLoad()
	{
		$this->assertTrue($this->loader['app']->has('View/TestView.php'));

		ob_start();

		$this->loader['app']->load('View/TestView.php');

		$content = ob_get_clean();

		$this->assertEquals('Test View Content', $content);
	}

	public function testLoadWithVariable()
	{
		ob_start();

		$this->loader['app']->load('View/TestView.php', array(
			'testData' => 'foo-bar'
			));

		$content = ob_get_clean();

		$this->assertEquals('Test View Contentfoo-bar', $content);
	}

	public function testGetContents()
	{
		$this->assertEquals('foo-bar', $this->loader['app']->getContents('text-dump'));
	}

	public function testAutoload()
	{
		$this->loader['app']->autoload('AutoloadedDir');

		$this->assertEquals(FooBarClass::CLASS, get_class(new FooBarClass));

		$this->loader['app']->autoload('AutoloadedDir', 'FooSpace');

		$this->assertEquals(\FooSpace\FooBazClass::CLASS, get_class(new \FooSpace\FooBazClass));
	}

	public function testBufferedLoad()
	{
		$content = $this->loader['app']->loadBuffered('dynamic-dump.php', array('foo' => 'bar'));

		$this->assertEquals('dynamic dump bar', $content);
	}
}