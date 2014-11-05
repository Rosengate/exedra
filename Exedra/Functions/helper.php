<?php

### return result with based on : _all, except:, and list,of,item
function filter_array($listR,$param,$isAssoc = null,$createnonexistcolumn = false)
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

function zeronating($val,$total)
{
	$zeros	= $total-strlen($val);
	return str_repeat("0", $zeros).$val;
}

function encryptNo($id,$type = "encrypt",$additionalNo = 26,$multiplier = 9999)
{
	$letterR	= Array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
	$zeroLevel		= strlen($multiplier);

	switch($type)
	{
		case "encrypt":
			$id		+= $additionalNo;
			$no 	= $id;
			

			$letter = "";


			//get current letter
			for($i=0;$i<26;$i++)
			{
				if($i < ($id/$multiplier))
				{
					$letter	= $letterR[$i];
					$deducter	= $i*$multiplier;
				}
			}
			return $letter.zeronating(($no-$deducter),$zeroLevel);
		break;
		case "decrypt":
			$currentLetter	= substr($id,0,1);

			## die because length not same.
			if($zeroLevel+1 != strlen($id))
			{
				return false;
			}

			$currKey		= array_search($currentLetter,$letterR);

			$realBalance		= $currKey*$multiplier;

			//get currentNo
			$currNo			= substr($id,1,$zeroLevel);

			return $currNo+$realBalance-$additionalNo;
		break;
	}
}

### get now date in Y-m-d H:i:s format.
function now()
{
	return date("Y-m-d H:i:s");
}

#### function to refine path, by changing slashes to work either in WINNT or Linux
function refine_path($path)
{
	switch(PHP_OS)
	{
		case "WINNT":
		return str_replace("/","\\",$path);
		break;
		case "Linux":
		return str_replace("\\", "/", $path);
		break;
	}
}

function concat_path($first_path,$path)
{
	return trim($first_path,"/").($path?"/".$path:"");
}

## replace string with param.
function replace_param($name)
{
	$paramR	= request::named();

	foreach($paramR as $key=>$value)
	{
		$name	= str_replace('{'.$key.'}',$value,$name);
	}

	return $name;
}

?>