<?php
namespace Exedra\Wizard\Tools;

class Table
{
	protected $header = array();

	protected $rows = array();

	protected $oneColRows = array();

	protected  $border;

	public function __construct($border = true)
	{
		$this->border = $border;
	}

	public function setHeader(array $array)
	{
		$this->header = $array;
	}

	public function addRow($records)
	{
		if(!is_array($records))
			return $this->addOneColumnRow($records);
		
		if(count($this->header) !== 0 && count($records) != count($this->header))
			throw new \Exedra\Exception\InvalidArgumentException("\Exedra\Console\Table : Row records must be same with headers (".count($this->header).")");

		$this->rows[] = $records;
	}

	public function addOneColumnRow($value)
	{
		$this->oneColRows[] = $value;
	}

	public function getRowCounts()
	{
		return count($this->rows);
	}

	public function printTable()
	{
		$lengths = array();

		$totalLength = 0;

		$rows = $this->rows;

		// to calculate length
		$rows[] = $this->header;

		foreach($rows as $record)
		{
			foreach($record as $no => $value)
			{
				if(!isset($lengths[$no]))
					$lengths[$no] = strlen($value);

				if(strlen($value) > $lengths[$no])
					$lengths[$no] = strlen($value);
			}
		}

		// create format
		$mask = $this->border ? '|' : ' ';
		$totalLength = 2 * count($lengths) + count($lengths) - 1;

		foreach($lengths as $length)
		{
			$mask .= ' %-'.$length.'s '.($this->border ? '|' : ' ');
			$totalLength += $length;
		}

		$mask .= "\n";

		$header = $this->header;
		$rows = $this->rows;

		$totalLength = $totalLength < 0 ? 0 : $totalLength;

		$line = '+'.str_repeat('-', $totalLength)."+\n";

		if(count($this->header) > 0)
		{
			if($this->border)
				echo $line;
			
			// header
			$params = array_merge(array($mask), $header);

			echo call_user_func_array('sprintf', $params);
		}
		else
		{
			$params = array($mask);
		}

		if($this->border)
			echo $line;

		// rows
		foreach($rows as $row)
		{
			$params = array_merge(array($mask), $row);
			echo call_user_func_array('sprintf', $params);
		}

		// one column rows
		foreach($this->oneColRows as $value)
		{
			$length = $totalLength;

			$border = $this->border ? '|' : ' ';

			$lengo = ($length/2) - (strlen($value)/2);

			$lengo = $lengo < 0 ? 0 : $lengo;

			$space = str_repeat(' ', $lengo);

			$mask = $border.$space.'%-'.($length - (strlen($space) * 2)).'s'.$space.$border."\n";

			echo sprintf($mask, $value);
		}

		if($this->border)
			echo $line;
	}
}