<?php
namespace Exedra\Console\Tools;

class Table
{
	protected $header = array();

	protected $rows = array();

	protected $oneColRows = array();

	public function setHeader(array $array)
	{
		$this->header = $array;
	}

	public function addRow($records)
	{
		if(!is_array($records))
			return $this->addOneColumnRow($records);
		
		if(count($this->header) !== 0 && count($records) != count($this->header))
			throw new \Exception("\Exedra\Console\Table : Row records must be same with headers (".count($this->header).")");

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
		$mask = '|';
		$totalLength = 2 * count($lengths) + count($lengths) - 1;
		foreach($lengths as $length)
		{
			$mask .= ' %-'.$length.'s |';
			$totalLength += $length;
		}

		$mask .= "\n";

		$header = $this->header;
		$rows = $this->rows;

		$line = '+'.str_repeat('-', $totalLength)."+\n";

		echo $line;

		// heaer
		$params = array_merge(array($mask), array_map('strtolower', $header));
		echo call_user_func_array('sprintf', $params);

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

			$space = str_repeat(' ', ($length/2) - (strlen($value)/2));
			$mask = '|'.$space.'%-'.($length - (strlen($space) * 2)).'s'.$space.'|'."\n";


			echo sprintf($mask, $value);
		}

		echo $line;
	}
}