<?php
class FormTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->form = new \Exedra\Form\Form;
	}

	public function testInputText()
	{
		$this->assertEquals('<input type="text" name="foo" id="foo" value="bar"  />', (string) $this->form->text('foo')->value('bar'));
	}

	public function testInputTextarea()
	{
		$this->assertEquals('<textarea name="foo" id="foo" >bar</textarea>', (string) $this->form->textarea('foo')->value('bar'));
	}

	public function testInputSelect()
	{
		$input = $this->form->select('foo')->options(array(1 => 'bar'))->attr('disabled', true);

		$this->assertEquals('<select name="foo" id="foo" disabled="1"><option  value="1">bar</option></select>', (string) $input);
	}
}