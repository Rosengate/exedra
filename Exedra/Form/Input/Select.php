<?php
namespace Exedra\Form\Input;

class Select extends Base
{
	protected $options = array();

	protected $firstValue = null;

	public function __construct($name, array $options = array())
	{
		parent::__construct($name);
		if(count($options) > 0)
			$this->options($options);
	}

	/**
	 * Set select options
	 * @param array options
	 * @return this
	 */
	public function options(array $options)
	{
		$this->options = $options;

		return $this;
	}

	/**
	 * Set first option
	 * If label is not passed, value will equal to '', and label set be using value
	 * @param string value
	 * @param string label (optional)
	 * @return this
	 */
	public function first($value, $label = null)
	{
		$this->firstValue = array(
			'label' => $label === null ? $value : $label,
			'value' => $label === null ? '' : $value
		);

		return $this;
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
	 * Construct the html
	 * @return string
	 */
	public function toString()
	{
		$attributes = $this->buildAttributes();

		$select = array();
		$select[] = '<select '.$attributes.'>';

		$firstValue = $this->firstValue;
		
		$value = isset($this->override) ? $this->override : (isset($this->attributes['value']) ? $this->attributes['value'] : '');

		if($firstValue)
		{
			$selected = $value == $firstValue['value'] ? 'selected' : '';
			$select[] = '<option '.$selected.' value="'.$firstValue['value'].'">'.$firstValue['label'].'</option>';
		}

		foreach($this->options as $val => $label)
		{
			if(is_array($label))
			{
				$select[] = '<optgroup label="'.$val.'">';
				foreach($label as $v => $l)
				{
					$selected = $v === $value ? 'selected' : '';
					$select[] = '<option '.$selected.' value="'.$v.'">'.$l.'</option>';
				}
				$select[] = '</optgroup>';
			}
			else
			{
				$selected = $val === $value ? 'selected' : '';
				$select[] = '<option '.$selected.' value="'.$val.'">'.$label.'</option>';
			}
		}

		$select[] = '</select>';

		return implode('', $select);
	}
}