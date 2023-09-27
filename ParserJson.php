<?php
$logFile = 'access_log';
$data = parseFile($logFile);
$output = convertToJson($data);

file_put_contents('output.json', $output);

/**
 *@param string $fileName
 *@return array
 */
function parseFile(string $fileName): array {
	$lines = file($fileName, FILE_IGNORE_NEW_LINES);
	
	$data = array();

	$Views = parseArray($lines, '@(\d{1,3}.\d{1,3}.\d{1,3}.\d{1,3})@', 0);
	$Url = parseArray($lines, "@(?i)\b((?:https?://|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\".,<>?«»“”‘’]))@", 0);
	$Traffic = parseArray($lines, '@\s(\d{3})\s(\d{1,100})\s@', 2);
	$StatusCodes = parseArray($lines, '@\s(\d{3})\s(\d{1,100})\s@', 1);
	$Crawler = parseArray($lines, '@"([a-zA-Z0-9]+)/\d{1,10}.+@', 1);

	$data['views'] = count($Views);
	$data['urls'] = count(getUniqueKey($Url));
	$data['traffic'] = array_sum($Traffic);
	$data['linesCount'] = getFileLength($fileName);
	$data['statusCodes'] = getCountedElements($StatusCodes);
	$data['crawlers'] = getCountedElements($Crawler);

	return $data;
}

/**
 *@param array $array
 *@return array
 */
function getUniqueKey(array $array): array {
	$uniqueKey = array();
	
	foreach ($array as $key_one => $value_one) {
		$count = 0;
		foreach ($array as $key_two => $value_two) {
			if($value_one === $value_two){$count += 1;}
		}
		if($count === 1){$uniqueKey[] = $value_one;}
	}
	return $uniqueKey;
}

/**
 *@param array $array
 *@return array
 */
function getCountedElements(array $array): array {
	$Items = array();
	foreach (array_unique($array) as $key_one => $value_one) {
		$count = 0;
		foreach ($array as $key_two => $value_two) {	
			if($value_one === $value_two){$count+=1;}
		}
		$Items[$value_one] = $count;
	}
	return $Items;
}

/**
 *@param array $data
 *@return string
 */
function convertToJson(array $data): string {
	$result = '';

	foreach ($data as $key => $value) {
		if(is_array($value)){
			$jsonValue = convertToJson($value);
			if(is_string($key)){
				$result = "$result\"$key\":$jsonValue,";
			}
			else{
				$result = "$result$jsonValue,";
			}			
		}
		else{
			$result = "$result\"$key\":\"$value\",";
		}
	}
	
	$result = str_replace(', }', '}', "{ $result }");
	return $result;
}

/**
 *@param string fileName
 *@return int
 */
function getFileLength(string $fileName): int {
	$lineCount = 0;
	$File = fopen($fileName, 'r');

	while(!feof($File)){
  		$line = fgets($File);
  		$lineCount++;
  	}

	fclose($File);
	return $lineCount;
}

/**
 *@param array $array
 *@param string $pattern
 *@param int $matchIndex
 *@return array
 */
function parseArray(array $array, string $pattern, int $matchIndex): array {
	$data = array();

	foreach ($array as $line) {
		if(preg_match($pattern, $line, $matches)){
			$data[] = $matches[$matchIndex];
		}
	}

	return $data;
}

?>
