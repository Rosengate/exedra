<?php
class LoaderTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->loader = new \Exedra\Loader(__DIR__.'/Factory/TestApp');
	}

	public function testLoad()
	{
		$this->assertTrue($this->loader->has('View/TestView.php'));

		ob_start();

		$this->loader->load('View/TestView.php');

		$content = ob_get_clean();

		$this->assertEquals('Test View Content', $content);
	}

	public function testLoadWithVariable()
	{
		ob_start();

		$this->loader->load('View/TestView.php', array(
			'testData' => 'foo-bar'
			));

		$content = ob_get_clean();

		$this->assertEquals('Test View Contentfoo-bar', $content);
	}

	public function testGetContents()
	{
		$this->assertEquals('foo-bar', $this->loader->getContents('text-dump'));
	}

	public function testAutoload()
	{
		$this->loader->autoload('AutoloadedDir');

		$this->assertEquals(FooBarClass::CLASS, get_class(new FooBarClass));

		$this->loader->autoload('AutoloadedDir', 'FooSpace');

		$this->assertEquals(\FooSpace\FooBazClass::CLASS, get_class(new \FooSpace\FooBazClass));
	}
}