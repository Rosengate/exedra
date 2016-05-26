<?php
namespace Exedra\Form\Input;

class Textarea extends Base
{
	/**
	 * Get textarea opening tag html
	 * @return string
	 */
	public function open()
	{
		$attributes = $this->buildAttributes();

		return '<textarea '.$attributes.'>';
	}

	/**
	 * Build input attribute
	 * Also build input class attribtue
	 * @return string
	 */
	protected function buildAttributes()
	{
		$attrs = array();

		$class = '';

		$attributes = $this->attributes;

		if(isset($attributes['value']))
			unset($attributes['value']);

		if(count($this->classes) > 0)
			$class = 'class="'.implode(' ', $this->classes).'" ';
		
		if(count($this->attributeString) > 0)
			$attrs = $this->attributeString;

		foreach($attributes as $key => $value)
			$attrs[] = $key.'="'.$value.'"';

		return $class.implode(' ', $attrs);
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
