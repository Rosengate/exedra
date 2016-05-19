<?php
class FactoryFileTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->basePath = new \Exedra\Path(__DIR__);

		$this->file = new \Exedra\Factory\File($this->basePath, 'PathTest.php');
	}

	public function testPathCreateFile()
	{
		$this->assertEquals($this->basePath->file('PathTest.php'), $this->file);
	}

	public function testName()
	{
		$this->assertEquals(realpath((string) $this->file), realpath(__DIR__.'/PathTest.php'));
	}

	public function testGetSplInfo()
	{
		$this->assertTrue($this->file->getSplInfo() instanceof \SplFileInfo);
	}

	public function testLoadBuffered()
	{
		$this->assertEquals('dynamic dump bar', $this->basePath->file('app/dynamic-dump.php')->loadBuffered(array('foo' => 'bar')));
	}
}