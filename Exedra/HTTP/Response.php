<?php
namespace Exedra\HTTP;

/**
 * Simple service class to help dealing with http response 
 * To be overhauled and follow PSR-7
 */

class Response
{
	/**
	 * HTTP Protocol
	 * @var string
	 */
	protected $protocol = "HTTP/1.1";

	/**
	 * HTTP Response status message
	 * @var int
	 */
	protected $status = 200;

	/**
	 * HTTP Response message
	 * @var string
	 */
	protected $message;

	/**
	 * Get status message
	 * @param int status
	 * @return string
	 */
	protected function statusMessages($status)
	{
		$statusMessages = array(
			100 => "continue",
			101 => "Switching Protocols",
			102 => "Processing",
			200 => "OK",
			201 => "Created",
			202 => "Accepted",
			203 => "Non-Authorative Information",
			204 => "No Content",
			205 => "Reset Content",
			206 => "Partial Content",
			207 => "Multi-Status",
			208 => "Already Reported",
			226 => "IM Used",
			300 => "Multiple Choices",
			301 => "Moved Permanently",
			302 => "Found",
			303 => "See Other",
			304 => "Not Modified",
			305 => "Use Proxy",
			306 => "Switch Proxy",
			307 => "Temporary Redirect",
			308 => "Permanent Redirect",
			400 => "Bad Request",
			401 => "Unauthorized",
			402 => "Payment Required",
			403 => "Forbidden",
			404 => "Not Found",
			405 => "Method Not Allowed",
			406 => "Not Acceptable",
			407 => "Proxy Authentication Required",
			408 => "Request Timeout",
			409 => "Conflict",
			410 => "Gone",
			411 => "Length Required",
			412 => "Precondition Failed",
			413 => "Request Entity Too Large",
			414 => "Request-URI Too Long",
			415 => "Unsupported Media Type",
			416 => "Request Range Not Satisfied",
			417 => "Expectation Failed",
			500 => "Internal Server Error",
			501 => "Not Implemented",
			502 => "Bad Gateway",
			503 => "Service Unavailable",
			504 => "Gateway Timeout"
			);

		return $statusMessages[$status];
	}

	/**
	 * Set status and message.
	 * @return this
	 */
	public function send()
	{
		// has custom status message, use it.
		$message = $this->message ? $this->message : $this->statusMessages($this->status);
		$status = $this->status;
		$protocol = $this->protocol;

		header($protocol.' '.$status.' '.$message);

		return $this;
	}

	/**
	 * Set custom status message
	 * @param string message
	 * @return this
	 */
	public function setMessage($message)
	{
		$this->message = $message;
		return $this;
	}

	/**
	 * Set status code
	 * @param int code
	 * @param string message (optional)
	 * @return this
	 */
	public function setStatus($code, $message = null)
	{
		if($message)
			$this->setMessage($message);

		$this->status = $code;
		return $this;
	}

	/**
	 * @return status code
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * Set header.
	 * @param mixed key
	 * @param string value
	 */
	public function header($key, $value = null)
	{
		if(is_array($key))
		{
			foreach($key as $k=>$v)
			{
				if(is_numeric($k))
					$this->header($v);
				else
					$this->header($k, $v);
			}
		}
		else if(is_string($key) && !$value)
		{
			header($key);
		}
		else
		{
			header($key.': '.trim($value));
		}

		return $this;
	}

	/**
	 * redirect.
	 */
	public function redirect($url)
	{
		$this->setStatus(302);
		$this->header('location', $url);die;
	}

	/**
	 * create a header for download.
	 * @param string filename
	 * @return this
	 */
	public function download($filename)
	{
		$this->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
		return $this;
	}
}