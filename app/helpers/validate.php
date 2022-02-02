<?php
namespace helpers;

class validate {

	public static $types = ['int', 'int_no_zero', '0/1', 'float', 'int_w_zero', 'date', 'time', 'regex', 'ip', 'username', 'password'];

	public static function filter_array($type, $array, $array2 = []) {
		if(empty($array))
			return [];

		$ret_arr = [];
		if($type == 'int') {
			$ret_arr = array_map('intval', array_filter($array, 'is_numeric'));
		}

		if($type == 'int_no_zero') {
			foreach ($array as $i => $k)
				if(intval($k) > 0)
					$array[$i] = intval($k);
				else
					unset($array[$i]);
			$ret_arr = $array;
		}


		if($type == 'in_array') {
			//$ret_arr = array_filter($array, function ($i) { return $i > 0 ?: false; });
			$ret_arr = array_intersect($array, $array2);
		}

		return $ret_arr;
	}

	public static function filter($type, $string, $settings = []) {
		$options = [];

		if($settings['min'] or $settings['max'])
			$options = self::options_min_max($settings['min'] >= 0?$settings['min']:false, $settings['max']?:false );

		if($type == 'int')
			$str = filter_var($string, FILTER_VALIDATE_INT, $options);

		if($type == 'int_no_zero') {
			$int = filter_var($string, FILTER_VALIDATE_INT, $options);
			$str = $int > 0 ? $int : false;
		}

		if($type == '0/1')
			$str = filter_var($string, FILTER_VALIDATE_INT, self::options_min_max(0, 1));

		if($type == 'float')
			$str = filter_var($string, FILTER_VALIDATE_FLOAT, $options) ?: false;

		if($type == 'int_w_zero' && preg_match("/^\d+/", $string, $matches) && $matches[0]) //0001, 0010 ok, 3ds => 3
			$str = $matches[0];

		if($type == 'date' && preg_match("/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/", $string, $matches) && $matches[0])
			$str = $matches[0];

		if($type == 'time' && preg_match("/^(\d{2}):(\d{2}):(\d{2})$/", $string, $matches) && $matches[0])
			$str = $matches[0];

		if($type == 'regex' && preg_match($settings['regex'], $string, $matches) && $matches[0])
			$str = $matches[0];

		if($type == 'ip')
			$str = filter_var($string, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ?: false;

		if($type == 'username' && preg_match("/[a-zA-Z0-9_]+/", $string, $matches) && $matches[0])
			$str = $matches[0];

		if($type == 'password' && preg_match("/[a-zA-Z0-9!@#$%^&*()_+]+/", $string, $matches) && $matches[0])
			$str = $matches[0];

		return $str !== false ? $str : false;
	}

	public static function options_min_max($min = false, $max = false) {
		return [ 'options' => ['min_range' => $min] + ( $max !== false ? ['max_range' => $max] : []) ];
	}

	public static function min_max($num, $min, $max) {
		$num = self::filter('int', $num);
		if($num >= $min && $num <= $max)
			return $num;
		return false;
	}
}