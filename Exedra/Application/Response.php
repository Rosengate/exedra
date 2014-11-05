<?php
namespace Exedra\Application;

class Response
{
	private $format;
	private $data;

	public function __construct()
	{
		
	}

	public function create($format,$data = Array())
	{
		if(!in_array($format,Array("json","view")))
		{
			throw new \Exception("Response format unknown : $format");
		}


		switch($format)
		{
			case "json":
			$this->setFormat($format);
			$this->setData($data);
			break;
		}

		return $this;
	}

	public function setFormat($format)
	{
		$this->format	= $format;
	}

	public function setData($data)
	{
		$this->data	= $data;
	}

	public function getFormat()
	{
		return $this->format;
	}

	public function getData()
	{
		return $this->data;
	}
}



?>