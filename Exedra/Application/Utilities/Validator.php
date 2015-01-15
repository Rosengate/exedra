<?php namespace Exedra\Application\Utilities;

class Validator
{
	/**
	 * Validate an associative data.
	 * @param array item
	 * @param array rules for the data.
	 */
	public function validate($item, $rules)
	{
		$result	= Array();
		$checked	= Array();

		foreach($rules as $filter=>$rule)
		{
			## filter item based on rule. 1. except, 2. selection, and 3. all.
			$filteredItemR	= $this->filter_array($item,$filter,null,true);

			$ruleR	= !is_array($rule)?explode("|",$rule):$rule;

			## loop filtered item
			foreach($filteredItemR as $key=>$val)
			{
				## loop rule of the current item/s
				foreach($ruleR as $rule_key=>$rule)
				{
					if($rule_key === "callback")
					{
						if($rule[0] == false && !in_array($key,$checked))
						{
							$result[$key]	= $rule[1];
						}
						continue;
					}

					list($rule,$message)	= explode(":",$rule);

					if(!self::_validate($val,$rule))
					{
						## validate once only, if already got error, permit no more check.
						if(in_array($key,$checked))
						{
							continue;
						}

						$checked[]	= $key;
						$result[$key]	= $this->getRuleMessage($rule,$message);
					}
				}
			}
		}

		return count($result) > 0?$result:false;
	}

	private function _validate($value,$rule)
	{
		if(strpos($rule,"min_length") === 0)
		{
			$length		= self::getRuleValue("min_length",$rule);
			if(strlen($value) <= $length)
			{
				return false;
			}
		}

		switch($rule)
		{
			case "required":
			if($value == "")
			{
				return false;
			}
			break;
			case "email":
				$mail_pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/";
				if(!preg_match($mail_pattern,trim($value)))
				{
					return false;
				}
			break;
			case "isString":
				return is_string($value);
			break;
		}

		return true;
	}

	private function getRuleMessage($rule,$curr = null)
	{
		$msg	= $curr?$curr:(isset(self::$message[$rule])?self::$message[$rule]:null);
		if(strpos($rule,"min_length") === 0)
		{
			$msg	= !$msg?self::$message['min_length']:$msg;
			$length	= self::getRuleValue("min_length",$rule);
			$msg	= str_replace("{length}",$length,$msg);
		}

		return $msg;
	}

	### return result with based on : _all, except:, and list,of,item
	/**
	 * Filter the given data with string based filter.
	 * @param array list
	 * @param string param
	 * @param mixed isAssoc
	 * @param boolean createnonexistcolumn (not sure what)
	 * @return filtered data. 
	 */
	public function filter_array($listR,$param,$isAssoc = null,$createnonexistcolumn = false)
	{
		$isAssoc	= $isAssoc === true?true:($isAssoc === false?false:(array_values($listR) === $listR?false:true));

		if($param == "_all")
		{
			return $listR;
		}

		$exception	= strpos($param, "except:") === 0;
		$param	= $exception?substr($param, 7,strlen($param)):$param;
		$paramR	= explode(",",$param);
		$result	= Array();

		## usable in validator. if selected param, didn't exists in listR, create one.
		if($createnonexistcolumn && !$exception)
		{
			foreach($paramR as $key)
			{
				if(!isset($listR[$key]))
				{
					$listR[$key]	= null;
				}
			}
		}

		foreach($listR as $key=>$val)
		{
			if(($isAssoc && !in_array($key,$paramR) && $exception) || ($isAssoc && in_array($key,$paramR) && !$exception))
			{
				$result[$key]	= $val;
			}
			else if((!$isAssoc && !in_array($val,$paramR) && $exception) || (!$isAssoc && in_array($val, $paramR) && !$exception))
			{
				$result[]	= $val;
			}
		}

		return $result;
	}
}