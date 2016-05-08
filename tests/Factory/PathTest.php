<?php
class FactoryPathTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$app = new \Exedra\Application(__DIR__);

		$this->factoryPath = new \Exedra\Application\Factory\Path($app->loader);
	}

	public function testCreate()
	{
		$this->testPath = $this->factoryPath->create('PathTest.php');

		$this->assertEquals($this->testPath, __DIR__.DIRECTORY_SEPARATOR.'PathTest.php');
	}

	public function testMulticreate()
	{
		$path = $this->factoryPath->create('folder');

		$newPath = $path->create('doriyaki/dom');

		$this->assertEquals($path.DIRECTORY_SEPARATOR.'doriyaki'.DIRECTORY_SEPARATOR.'dom', (string) $newPath);
	}

	public function testClassName()
	{
		$this->testCreate();

		$this->assertEquals($this->testPath instanceof \Exedra\Application\Factory\Path, true);
	}

	public function testCheckExists()
	{
		$this->testCreate();

		$this->assertEquals($this->testPath->isExists(), true);
	}

	public function testGetContent()
	{
		$this->testCreate();

		$this->assertEquals($this->testPath->getContent(), file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'PathTest.php'));
	}

	public function testInvoke()
	{
		$path = $this->factoryPath->create('foo');

		$ns = DIRECTORY_SEPARATOR;

		$this->assertEquals($path('bar/baz')->create('qux'), $path.$ns.'bar'.$ns.'baz'.$ns.'qux');
	}
}
