<?php
use Exedra\Support\DotArray;

class DotArrayTest extends BaseTestCase
{
	public function caseSetUp()
	{
		$this->storage = array();
	}

	public function testSet()
	{
		DotArray::set($this->storage, 'foo.bar', 'baz');

		DotArray::set($this->storage, 'foo.baz', 'qux');

		$this->assertEquals($this->storage['foo']['bar'], 'baz');

		$this->assertEquals($this->storage['foo'], array('bar' => 'baz', 'baz' => 'qux'));
	}

	public function testGet()
	{
		$this->storage = array(
			'foo' => array(
				'baz' => 'bad',
				'qux' => array(
					'nux' => 'bud'
					)
				),
			'dux' => array(
				'tuq' => array(
					'jux' => 'dum'
					)
				)
			);

		$this->assertEquals(DotArray::get($this->storage, 'foo.baz'), 'bad');

		$this->assertEquals(DotArray::get($this->storage, 'foo.qux.nux'), 'bud');

		$this->assertEquals(DotArray::get($this->storage, 'dux.tuq.jux'), 'dum');
	}

	public function testHas()
	{
		$this->storage = array(
			'foo' => array(
				'baz' => 'bad',
				'qux' => array(
					'nux' => 'bud'
					)
				),
			'dux' => array(
				'tuq' => array(
					'jux' => 'dum'
					)
				)
			);

		$this->assertTrue(DotArray::has($this->storage, 'foo.baz'));

		$this->assertTrue(DotArray::has($this->storage, 'foo.qux.nux'));

		$this->assertTrue(DotArray::has($this->storage, 'dux.tuq.jux'));

		$this->assertFalse(DotArray::has($this->storage, 'dux.tuq.dax'));
	}

	public function testDelete()
	{
		DotArray::set($this->storage, 'foo.bar', 'baz');

		DotArray::set($this->storage, 'foo.baz', 'qux');

		DotArray::set($this->storage, 'foo.tuq', 'nux');

		$this->assertEquals($this->storage['foo']['bar'], 'baz');

		DotArray::delete($this->storage, 'foo.bar');

		$this->assertTrue(!isset($this->storage['foo']['bar']));

		$this->assertEquals($this->storage['foo']['baz'], 'qux');

		DotArray::delete($this->storage, 'foo');

		$this->assertTrue(!DotArray::has($this->storage, 'foo.tuq.nux'));
	}

	public function testEach()
	{
		$dump = array(
			'foo' => array(
				'bar' => 'baz',
				'quz' => array(
					'tux' => 'nux'
					)
				),
			'juz' => 'tuq',
			'dax' => array(
				'nut' => 'jar'
				)
			);

		$new = array();

		DotArray::each($dump, function($key, $value) use(&$new)
		{
			$new[] = $key;
		});

		$this->assertEquals(array(
			'foo.bar',
			'foo.quz.tux',
			'juz',
			'dax.nut'
			), $new);
	}
}