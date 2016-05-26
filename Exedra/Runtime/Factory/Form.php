<?php
namespace Exedra\Runtime\Factory;

class Form extends \Exedra\Form\Form
{
	public function __construct(\Exedra\Runtime\Exe $exe)
	{
		$this->exe = $exe;

		$this->initialize();
	}

	/**
	 * Initialize with flash data
	 * @param array data
	 */
	public function initialize(array $data = array())
	{
		if($this->exe->flash->has('form_data'))
			$this->set($this->exe->flash->get('form_data'));

		parent::initialize($data);
	}

	/**
	 * Flash given data
	 * If not passed, flash form data
	 * @param array data
	 * @return self
	 */
	public function flash(array $data = array())
	{
		if(count($data) == 0 && $this->exe->request === null)
			return;

		$data = count($data) > 0 ? $data : $this->exe->request->getParsedBody();

		$this->exe->flash->set('form_data', $data);

		return $this;
	}
}

?>