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

	/**
	 * Close and response
	 * http://stackoverflow.com/questions/138374/close-a-connection-early
	 */
	public function close()
	{
		$contents = ob_get_clean();
		header("Content-Encoding: none\r\n");
		ignore_user_abort(true);
		ob_start();
		echo $contents;
		$size = ob_get_length();
		header("Content-Length: $size");
		ob_end_flush();
		flush();
		ob_end_clean();
	}
}