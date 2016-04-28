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

	public function testClassName()
	{
		$this->testCreate();

		$this->assertEquals($this->testPath instanceof \Exedra\Application\Factory\Blueprint\Path, true);
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
}
