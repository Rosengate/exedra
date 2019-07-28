<?php

namespace Exedra\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Response extends Message implements ResponseInterface
{
    protected $status;

    protected $reasonPhrase = null;

    protected $statuses;

    public function __construct($status = 200, array $headers = array(), StreamInterface $body, $protocol = '1.1', $reason = null)
    {
        parent::__construct($headers, $body, $protocol);

        $this->statuses = array(
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authorative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-Status',
            208 => 'Already Reported',
            226 => 'IM Used',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => 'Switch Proxy',
            307 => 'Temporary Redirect',
            308 => 'Permanent Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Request Range Not Satisfied',
            417 => 'Expectation Failed',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout'
        );

        $this->status = $status;

        $this->reasonPhrase = $reason;
    }

    public function __clone()
    {
        $this->body = clone $this->body;
    }

    /**
     * @return static
     */
    public static function createEmptyResponse()
    {
        return new static(200, array(), new Stream(fopen('php://temp', 'r+')));
    }

    /**
     * @param ResponseInterface $response
     * @return static
     */
    public static function createFromPsrResponse(ResponseInterface $response)
    {
        return new static($response->getStatusCode(), $response->getHeaders(), $response->getBody(), $response->getProtocolVersion(), $response->getReasonPhrase());
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->status;
    }

    /**
     * @param $status
     * @param null $reason
     * @return $this
     */
    public function setStatus($status, $reason = null)
    {
        $this->status = $status;

        $this->reasonPhrase = $reason;

        return $this;
    }

    /**
     * @param int $status
     * @param null $reason
     * @return Response
     */
    public function withStatus($status, $reason = null)
    {
        $response = clone $this;

        return $response->setStatus($status, $reason);
    }

    /**
     * @return mixed|null|string
     */
    public function getReasonPhrase()
    {
        if ($this->reasonPhrase)
            return $this->reasonPhrase;

        return isset($this->statuses[$this->status]) ? $this->statuses[$this->status] : 'Unknown';
    }

    /**
     * @param $status
     * @return mixed|string
     */
    public function getDefaultReasonPhrase($status)
    {
        return isset($this->statuses[$status]) ? $this->statuses[$status] : 'Unknown';
    }

    /**
     * Old method
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function header($name, $value)
    {
        return $this->setHeader($name, $value);
    }

    /**
     * Send header
     */
    public function sendHeader()
    {
        if ($this->reasonPhrase || $this->protocol != '1.1')
            header('HTTP/' . $this->getProtocolVersion() . ' ' . $this->status . ' ' . $this->getReasonPhrase());
        else
            http_response_code($this->status);

        foreach ($this->headerLines as $key => $values)
            header($key . ': ' . implode(', ', $values));
    }

    /**
     * Send header and print response
     * @return void
     */
    public function send()
    {
        $this->sendHeader();

        $body = $this->getBody();

        $body->rewind();

        echo $body->getContents();
    }

    /**
     * Set location header (redirect)
     * @param string $url
     * @return $this
     */
    public function redirect($url)
    {
        $this->setHeader('Location', $url);

        return $this;
    }

    /**
     * Set Refresh header
     * @param int $time
     * @return $this
     */
    public function refresh($time = 0)
    {
        $this->setHeader('Refresh', $time);

        return $this;
    }

    /**
     * Close and respond
     * http://stackoverflow.com/questions/138374/close-a-connection-early
     */
    public function close()
    {
        $contents = ob_get_clean();
        header("Connection: close\r\n");
        header("Content-Encoding: none\r\n");
        ignore_user_abort(true);
        ob_start();
        echo $contents;
        $size = ob_get_length();
        header("Content-Length: $size");
        if (function_exists('fastcgi_finish_request'))
            fastcgi_finish_request();
        ob_end_flush();
        flush();
        ob_end_clean();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getBody()->toString();
    }
}