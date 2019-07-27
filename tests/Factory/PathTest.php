<?php
class FactoryPathTest extends \BaseTestCase
{
	public function caseSetUp()
	{
		$this->path = new \Exedra\Path(__DIR__);

		$this->assertEquals(realpath($this->path->to('PathTest.php')), realpath(__DIR__.'/'.'PathTest.php'));
	}

	public function testClassName()
	{
		$this->assertTrue($this->path instanceof \Exedra\Path);
	}

	public function testCheckExists()
	{
		$this->assertEquals($this->path->isExists(), true);
	}

	public function testGetContent()
	{
		$this->assertEquals($this->path->getContents('PathTest.php'), file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'PathTest.php'));
	}

	/*public function testCreateFile()
	{
		$this->assertTrue($this->path->file('PathTest.php')->isExists());

		$this->assertTrue($this->path->file('PathTest.php')->getSplInfo());
	}*/

	public function testInvoke()
	{
		$path = $this->path->create('foo');

		$ns = DIRECTORY_SEPARATOR;

		// $this->assertEquals($path('bar/baz')->create('qux'), $path.$ns.'bar'.$ns.'baz'.$ns.'qux');
	}
}
