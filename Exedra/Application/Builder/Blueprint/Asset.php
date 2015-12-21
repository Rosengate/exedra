<?php
namespace Exedra\Application\Builder\Blueprint;

class Asset
{
	protected $filepath;

	protected $type;

	public function __construct(\Exedra\Application\Execution\Exec $exe, $type, $filepath, $filename)
	{
		if(!in_array($type, array('js', 'css')))
			$exe->exception->create('Only js and css be used.');

		$this->exe = $exe;
		$this->type = $type;
		$this->filepath = $filepath;
		$this->filename = $filename;
	}

	/**
	 * @return string
	 */
	protected function getTypeTag()
	{
		$typeTags = array('css' => 'style', 'js' => 'script');

		return $typeTags[$this->type];
	}

	/**
	 * Create an asset with the given closure to be buffered.
	 * @param \Closure
	 */
	public function create(\Closure $closure)
	{
		ob_start();
		echo '/*GENERATED AT '.date('Y-m-d H:i:s').'*/';
		$closure();
		$content = ob_get_clean();

		// trim empty spaces and strip only <script> tags.
		$content = trim(preg_replace('/<\/?' . $this->getTypeTag() . '(.|\s)*?>/', '', $content));

		$dirs = explode(DIRECTORY_SEPARATOR, $this->filepath);
		array_pop($dirs);
		$dirs = implode(DIRECTORY_SEPARATOR, $dirs);

		if(!is_dir($dirs))
		{
			mkdir($dirs, '755', true);
		}

		// keep replacing those content
		file_put_contents($this->filepath, $content);


		return $this;
	}

	/**
	 * Get url of the current asset
	 * @return string
	 */
	public function url()
	{
		return $this->exe->url->asset($this->filename);
	}

	/**
	 * Return html tag format of the asset.
	 * @return string
	 */
	public function tag()
	{
		switch($this->type)
		{
			case 'js':
				return '<script type="text/javascript" src="'.$this->url().'"></script>';
			break;
			case 'css':
				return '<link rel="stylesheet" type="text/css" href="'.$this->url().'">';
			break;
		}
	}

	/**
	 * Alias to tag(). Return string of
	 * @return string
	 */
	public function __toString()
	{
		return $this->tag();
	}
}