<?php
namespace Exedra\HTTP;

/**
 * A placeholder for upcoming PSR-7 impementation
 * implements Psr\Http\Message\MessageInterface
 */
class Message
{
	protected $protocol = '1.1';

	protected $headers = array();

	public function getProtocolVersion()
	{
		return $this->protocol;
	}

	public function withProtocolVersion()
	{

	}

	public function hasHeader()
	{

	}

	public function getHeaders()
	{
		return $this->headers;
	}

	public function getHeader($name)
	{

	}

	public function getHeaderLine($name)
	{

	}

	public function withHeader($name, $value)
	{

	}

	public function withAddedHeader()
	{

	}

	public function withoutHeader()
	{

	}

	/**
	 * @return \Exedra\HTTP\Stream
	 */
	public function getBody()
	{

	}

	public function withBody(Stream $body)
	{
		
	}
}