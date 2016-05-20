<?php
class FactoryFileTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->basePath = new \Exedra\Path(__DIR__);

		$this->file = new \Exedra\File($this->basePath->to('PathTest.php'));
	}

	public function testPathCreateFile()
	{
		$this->assertEquals($this->basePath->file('PathTest.php'), $this->file);
	}

	public function testName()
	{
		$this->assertEquals(realpath((string) $this->file), realpath(__DIR__.'/PathTest.php'));
	}

	public function testIsFileInfo()
	{
		$this->assertTrue($this->file instanceof \SplFileInfo);
	}

	public function testLoadBuffered()
	{
		$this->assertEquals('dynamic dump bar', $this->basePath->file('app/dynamic-dump.php')->loadBuffered(array('foo' => 'bar')));
	}

	public function testIsExists()
	{
		$this->assertTrue($this->file->isExists());
	}

	public function testOpen()
	{
		$this->assertTrue($this->file->open() instanceof \SplFileObject);
	}
}