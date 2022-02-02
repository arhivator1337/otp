<?php
namespace helpers;

class date {

	public static $days_bin = [
		1 => 0b0000001, //  1
		2 => 0b0000010, //  2
		3 => 0b0000100, //  4
		4 => 0b0001000, //  8
		5 => 0b0010000, // 16
		6 => 0b0100000, // 32
		7 => 0b1000000, // 64
	];

	public static $week_days = [
		1 => 'Monday',
		2 => 'Tuesday',
		3 => 'Wednesday',
		4 => 'Thursday',
		5 => 'Friday',
		6 => 'Saturday',
		7 => 'Sunday',
	];

	public static $week_days_short = [
		1 => 'mon',
		2 => 'tue',
		3 => 'wed',
		4 => 'thu',
		5 => 'fri',
		6 => 'sat',
		7 => 'sun',
	];

	public static function number_to_days(int $number):array {
		$days = [];
		for($i = 1; $i <= 7; $i++) {
//			$days[$i] = (self::$days_bin[$i] & $number) ? 1 : 0;
			$days[$i] = ['day' => self::$week_days[$i], 'day_short' => __('main.' . self::$week_days_short[$i]), 'active' => (self::$days_bin[$i] & $number) ? 1 : 0];
		}
		return $days;
	}

	public static function proper_array(array $array) {
		$days = [];
		for ($i = 1; $i <= 7; $i++)
			$days[$i] = isset($array[$i]) ? 1 : 0;
		return $days;
	}

	public static function convert_to_number(array $days):int {
		return bindec(strrev(implode("", $days)));
	}

	public static function number_to_day(int $n_day):string {
		return strftime("%A", strtotime("Sunday +{$n_day} days"));
	}

	public static function short_days($len = 3) {
		foreach (self::$week_days as $n => $d)
			$k[] = substr(self::$week_days[$n], 0, $len);
		return $k;
	}

	public static function short_date($date = false) {
		return $date ? date('d-m-y', $date) : '';
	}

	public static function render_days($number, $symbol = 1) {
		//$array = self::number_to_days($number);
		//foreach ($array as $id => $)
	}

}