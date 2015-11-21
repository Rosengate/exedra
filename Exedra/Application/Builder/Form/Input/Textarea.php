<?php
namespace Exedra\Application\Builder\Form\Input;

class Textarea extends Base
{
	/**
	 * Get textarea opening tag html
	 * @return string
	 */
	public function open()
	{
		$name = $this->name;
		$id = $this->id ? : $name;
		$attributes = $this->buildAttributes();

		return '<textarea name="'.$name.'" id="'.$id.'" '.$attributes.'>';
	}

	/**
	 * Get textarea closing tag html
	 * @return string
	 */
	public function close()
	{
		return '</textarea>';
	}

	/**
	 * Construct the html
	 * @return string
	 */
	public function toString()
	{
		return $this->open().$this->getValue().$this->close();
	}
}
