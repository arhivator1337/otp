<?php
namespace helpers;

class arr {

	//search in array for combination key and value, returns only one result
	public static function search_key_val(array $array, string $column_key, string $column_value) {
		$index = array_search($column_value, array_column($array, $column_key));
		return $index !== false ? $array[$index] : false;
	}

	public static function search_key_vals(array $array, string $column_key, string $column_value) {
		$arrIt = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($array));

		foreach ($arrIt as $sub) {
			$subArray = $arrIt->getSubIterator();
			if ($subArray[$column_key] === $column_value)
				$outputArray[] = iterator_to_array($subArray);
		}
		return $outputArray;
	}

	public static function map_id($array, $id_to_map = 'id') {
		if(empty($array))
			return [];
		$res = [];

		foreach ($array as $i => $ar)
			$res[$ar[$id_to_map]] = $ar;
		return $res;
	}

	public static function map_key_val($array, $id_to_key = false, $id_to_val) {
		if(empty($array))
			return [];
		$res = [];
		foreach ($array as $i => $ar) {
			if ($id_to_key == false)
				$res[] = $ar[$id_to_val];
			else
				$res[$ar[$id_to_key]] = $ar[$id_to_val];
		}
		return $res;
	}

	public static function map_id_nested($array, $id_to_map, $nested_id_to_map = false) {
		$res = [];
		for ($i = 0; $i < count($array); $i++) {
			if($nested_id_to_map) {
				if (!empty($array[$i][$id_to_map]) && $array[$i][$nested_id_to_map])
					$res[$array[$i][$id_to_map]][$array[$i][$nested_id_to_map]] = $array[$i];
			} else {
				if (!empty($array[$i][$id_to_map]))
					$res[$array[$i][$id_to_map]][] = $array[$i];
			}
		}
		return $res;
	}
}