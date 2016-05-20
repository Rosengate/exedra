<?php
namespace Exedra\Runtime\Factory;

class Form extends \Exedra\Form\Form
{
	public function __construct(\Exedra\Runtime\Exe $exe)
	{
		$this->exe = $exe;

		if($exe->flash->has('form_data'))
			$this->set($exe->flash->get('form_data'));
	}

	/**
	 * Flash given data
	 * If not passed, flash form data
	 * @param array data
	 * @return self
	 */
	public function flash(array $data = array())
	{
		if(!$this->exe->request)
			return null;

		$data = count($data) > 0 ? $data : $this->exe->request->getParsedBody();

		if(count($data) > 0)
			$this->exe->flash->set('form_data', $data);

		return $this;
	}
}

?>