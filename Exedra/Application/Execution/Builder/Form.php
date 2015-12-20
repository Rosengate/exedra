<?php
namespace Exedra\Application\Execution\Builder;

class Form extends \Exedra\Application\Builder\Form\Form
{
	public function __construct(\Exedra\Application\Execution\Exec $exe)
	{
		$this->exe = $exe;

		if($exe->flash->has('form_data'))
			$this->set($exe->flash->get('form_data'));
	}

	/**
	 * Flash request->post() into form_data
	 */
	public function flash(array $data = array())
	{
		$data = count($data) > 0 ? $data : $this->exe->request->post();

		if(count($data) > 0)
			$this->exe->flash->set('form_data', $data);

		return $this;
	}
}

?>