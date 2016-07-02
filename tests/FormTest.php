<?php
class FormTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->form = new \Exedra\Form\Form;
	}

	public function testInputText()
	{
		$this->assertEquals('<input type="text" name="foo" id="foo" value="bar" />', (string) $this->form->text('foo')->value('bar'));

		$this->assertEquals('<input type="text"  />', (string) $this->form->text());

		$this->assertEquals('<input type="text" name="foo" id="bar" />', (string) $this->form->text()->name('foo')->id('bar'));
	}

	public function testInputTextarea()
	{
		$this->assertEquals('<textarea name="foo" id="foo"></textarea>', (string) $this->form->textarea('foo'));
		
		$this->assertEquals('<textarea name="foo" id="foo">bar</textarea>', (string) $this->form->textarea('foo')->value('bar'));
	}

	public function testInputSelect()
	{
		$input = $this->form->select('foo')->options(array(1 => 'bar'))->attr('disabled', true);

		$this->assertEquals('<select name="foo" id="foo" disabled="1"><option  value="1">bar</option></select>', (string) $input);

		$this->assertEquals('<select ></select>', (string) $this->form->select());

		$this->assertEquals('<select name="foo"></select>', (string) $this->form->select()->name('foo'));
	}

	public function testInputSubmit()
	{
		$this->assertEquals('<input type="submit"  />', (string) $this->form->submit());
	}


	public function testPrevalue()
	{
		$this->form->set('foo', 'bar');

		$this->form->set('baz', 'qux', true);

		$this->assertEquals('<input type="text" name="foo" id="foo" value="bar" />', (string) $this->form->text('foo'));

		$this->assertEquals('<input type="text" name="baz" id="baz" value="qux" />', (string) $this->form->text('baz')->value('tux'));
	}
}