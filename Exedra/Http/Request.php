<?php
namespace Exedra\Http;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * HTTP Request interface
 * Covers ServerRequestInterface with a number of added method
 */
class Request extends Message implements RequestInterface
{
    /**
     * HTTP Request URI instance
     * @param \Exedra\Http|Uri
     */
    protected $uri;

    protected $requestTarget;

    protected $method;

    public function __construct($method, Uri $uri, array $headers, Stream $body)
    {
        parent::__construct($headers, $body);

        $this->method = strtoupper($method);

        $this->uri = $uri;
    }

    public function __clone()
    {
        $request = clone $this;

        $request->uri = clone $this->uri;

        return $request;
    }

    public function getRequestTarget()
    {
        if($this->requestTarget)
            return $this->requestTarget;

        $target = $this->uri->getPath();

        if($target === null)
            $target = '/';

        if($query = $this->uri->getQuery())
            $target = '?'.$query;

        return $target;
    }

    public function setRequestTarget($target)
    {
        $this->requestTarget = $target;

        return $this->requestTarget;
    }

    public function withRequestTarget($target)
    {
        $request = clone $this;

        return $request->setRequestTarget($target);
    }

    public function setMethod($method)
    {
        $this->method = strtoupper($method);
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function withMethod($method)
    {
        $request = clone $this;

        $request->setMethod($method);

        return $request;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function setUri($uri)
    {
        if(is_string($uri) || is_array($uri))
            $this->uri = new Uri($uri);
        else if($uri instanceof Uri)
            $this->uri = $uri;
        else
            throw new \InvalidArgumentException('Invalid uri. Must be string, array, or Uri');
    }

    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $request = clone $this;

        $request->uri = $uri;

        return $request;
    }
}