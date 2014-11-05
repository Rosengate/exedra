<?php
namespace Exedra\Exedrian;

class Parser
{
	public function __construct()
	{

	}

	public function parse($response,$params = null)
	{
		if(is_string($response)) return $response;

		if(strtolower(get_class($response)) == "response")
		{
			switch($response->getFormat())
			{
				case "json":
				return $this->parseJSON($response);
				break;
				case "view":
				return $this->parseView($response,$params['loader'],$params['structure']);
				break;
			}
		}
		else if($response == "exception")
		{
			return $params['error_msg'];
		}
	}

	private function parseJSON($response)
	{
		return json_encode($response->getData());
	}

	private function parseView($response,Loader $loader,structure $structure)
	{

	}

	private function parseError($error,$code = 404)
	{
		return $error;
	}
}


?>