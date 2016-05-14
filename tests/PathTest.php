<?php
class PathTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->path = new \Exedra\Path(__DIR__.'/Factory');

		$this->path['app'] = 'app';
	}

	public function testLoad()
	{
		$this->assertTrue($this->path['app']->has('View/TestView.php'));

		ob_start();

		$this->path['app']->load('View/TestView.php');

		$content = ob_get_clean();

		$this->assertEquals('Test View Content', $content);
	}

	public function testLoadWithVariable()
	{
		ob_start();

		$this->path['app']->load('View/TestView.php', array(
			'testData' => 'foo-bar'
			));

		$content = ob_get_clean();

		$this->assertEquals('Test View Contentfoo-bar', $content);
	}

	public function testGetContents()
	{
		$this->assertEquals('foo-bar', $this->path['app']->getContents('text-dump'));
	}

	public function testAutoload()
	{
		$this->path['app']->autoload('AutoloadedDir');

		$this->assertEquals(FooBarClass::CLASS, get_class(new FooBarClass));

		$this->path['app']->autoload('AutoloadedDir', 'FooSpace');

		$this->assertEquals(\FooSpace\FooBazClass::CLASS, get_class(new \FooSpace\FooBazClass));
	}

	public function testBufferedLoad()
	{
		$content = $this->path['app']->loadBuffered('dynamic-dump.php', array('foo' => 'bar'));

		$this->assertEquals('dynamic dump bar', $content);
	}
}