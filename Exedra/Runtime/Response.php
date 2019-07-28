<?php

namespace Exedra\Runtime;

use Exedra\Http\Stream;
use Psr\Http\Message\ResponseInterface;

/**
 * An application execution oriented response
 * Extends the original \Exedra\Http\Response
 * Except that the response body itself is not a stream
 * Will be translated to HTTP response on dispatch
 */
class Response extends \Exedra\Http\Response
{
    /**
     * @param ResponseInterface $response
     * @return static
     */
    public static function createFromPsrResponse(ResponseInterface $response)
    {
        return new static($response->getStatusCode(), $response->getHeaders(), (string)$response->getBody(), $response->getProtocolVersion(), $response->getReasonPhrase());
    }

    public function setBody($body, $mode = null)
    {
        $this->body = $body;

        return $this;
    }

    public function write($contents)
    {
        if ($this->body instanceof Stream)
            $this->body->write($contents);
        else
            $this->body = $contents;

        return $this;
    }

    public function send()
    {
        $this->sendHeader();

        echo $this->body;
    }
}