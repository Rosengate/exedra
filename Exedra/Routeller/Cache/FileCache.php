<?php
namespace Exedra\Routeller\Cache;

class FileCache implements CacheInterface
{
    protected $dir;

    public function __construct($dir)
    {
        $this->dir = rtrim($dir, '/\\');

        if(!file_exists($this->dir))
            mkdir($this->dir, 0777, true);
    }

    public function set($key, array $entries, $lastModified)
    {
        $filename = $this->dir .'/'. $key .'.php';

        $cache = array(
            'last_modified' => $lastModified,
            'entries' => $entries
        );

        file_put_contents($filename, '<?php return ' .var_export($cache, true). ';');
    }

    public function get($key)
    {
        $filename = $this->dir .'/' .$key .'.php';

        if(file_exists($filename))
            return require $filename;

        return false;
    }

    public function clear($key)
    {
        unlink($this->dir .'/'. $key .'.php');
    }

    public function clearAll()
    {
        $files = glob($this->dir .'/*');

        foreach($files as $file){ // iterate files
            if(is_file($file))
                unlink($file);
        }
    }
}