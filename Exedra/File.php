<?php

namespace Exedra;

class File extends \SplFileInfo
{
    /**
     * Relative path of the file.
     * @var string|array
     */
    protected $filename;

    public function __construct($filename)
    {
        parent::__construct($filename);

        $this->filename = $filename;
    }

    /**
     * Check whether this file exists or not.
     * @return boolean
     */
    public function isExists()
    {
        return file_exists($this->filename);
    }

    /**
     * Require this instance's file path extracted with the given data (optional)
     * @param array $data
     * @return mixed
     */
    public function load(array $data = array())
    {
        extract($data);

        return require $this->filename;
    }

    /**
     * Require the file once
     * @param array $data
     * @return mixed
     */
    public function loadOnce(array $data = array())
    {
        extract($data);

        return require_once $this->filename;
    }

    /**
     * Load this file buffered.
     * @param array $data
     * @return mixed
     */
    public function loadBuffered(array $data = array())
    {
        ob_start();

        extract($data);

        require $this->filename;

        return ob_get_clean();
    }

    /**
     * Alias to getContent()
     * @return mixed
     */
    public function getContents()
    {
        return file_get_contents($this->filename);
    }

    /**
     * Open the file
     * @param string $mode
     * @return \SplFileObject
     *
     * @throws \RuntimeException
     */
    public function open($mode = 'r')
    {
        return new \SplFileObject((string)realpath($this->filename), $mode);
    }

    /**
     * Delete the file
     */
    public function delete()
    {
        unlink($this->filename);
    }

    /**
     * Put contents to the given path if it's file
     * @param string $data
     * @param int $flag file_put_contents flag
     * @param resource $context
     * @return mixed
     */
    public function putContents($data = null, $flag = null, $context = null)
    {
        file_put_contents($this->filename, $data, $flag, $context);
    }
}