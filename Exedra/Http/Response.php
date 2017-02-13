<?php
namespace Exedra\Http;

use Psr\Http\Message\ResponseInterface;

class Response extends Message implements ResponseInterface
{
    protected $status;

    protected $reasonPhrase = null;

    protected $statuses;

    public function __construct($status = 200, array $headers = array(), Stream $body, $protocol = '1.1', $reason = null)
    {
        parent::__construct($headers, $body, $protocol);

        $this->statuses =  array(
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

    public static function createEmptyResponse()
    {
        return new static(200, array(), new Stream(fopen('php://temp', 'r+')));
    }

    public function getStatusCode()
    {
        return $this->status;
    }

    public function setStatus($status, $reason = null)
    {
        $this->status = $status;

        $this->reasonPhrase = $reason;

        return $this;
    }

    public function withStatus($status, $reason = null)
    {
        $response = clone $this;

        return $response->setStatus($status, $reason);
    }

    public function getReasonPhrase()
    {
        if($this->reasonPhrase)
            return $this->reasonPhrase;

        return isset($this->statuses[$this->status]) ? $this->statuses[$this->status] : 'Unknown';
    }

    public function getDefaultReasonPhrase($status)
    {
        return isset($this->statuses[$status]) ? $this->statuses[$status] : 'Unknown';
    }

    /**
     * Old method
     * @param string $name
     * @param string $value
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
        if($this->reasonPhrase || $this->protocol != '1.1')
            header('HTTP/'.$this->getProtocolVersion().' '.$this->status.' '.$this->getReasonPhrase());
        else
            http_response_code($this->status);

        foreach($this->headerLines as $key => $values)
            header($key.': '.implode(', ', $values));
    }

    /**
     * Send header and print response
     * @return void
     */
    public function send()
    {
        $this->sendHeader();

        echo $this->getBody()->rewind()->getContents();
    }

    /**
     * Set location header (redirect)
     * @param string url
     */
    public function redirect($url)
    {
        $this->setHeader('Location', $url);
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

    public function __toString()
    {
        return $this->getBody()->toString();
    }
}