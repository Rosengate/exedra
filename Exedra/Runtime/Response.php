<?php
namespace Exedra\Runtime;

/**
 * An application execution oriented response
 * Extends the original \Exedra\Http\Response
 * Except that the response body itself is not a stream
 * Will be translated to HTTP response on dispatch
 */
class Response extends \Exedra\Http\Response
{
	public function setBody($body, $mode = null)
	{
		$this->body = $body;

		return $this;
	}

	public function send()
	{
		$this->sendHeader();

		echo $this->body;
	}
}