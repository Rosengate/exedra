<?php

require_once "Exedra/Exedra.php";

class BuilderPathTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$exedra = new \Exedra\Exedra(__DIR__);

		$this->builderPath = new \Exedra\Application\Builder\Path($exedra->loader);

	}

	public function testCreate()
	{
		$this->testPath = $this->builderPath->create('PathTest.php');

		$this->assertEquals($this->testPath, __DIR__.DIRECTORY_SEPARATOR.'PathTest.php');
	}

	public function testClassName()
	{
		$this->testCreate();

		$this->assertEquals($this->testPath instanceof \Exedra\Application\Builder\Blueprint\Path, true);
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
