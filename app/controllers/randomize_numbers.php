<?php
namespace controllers;

use helpers\validate;

class randomize_numbers extends \controllers\Controller {

	function __construct() {
		parent::__construct();
	}

	function index(\Base $app, $params) {
		$error = $attempts = $digits = false;
		$result = $numbers = [];

		if ($app->get('SERVER.REQUEST_METHOD') == 'POST' && $app->get('POST.numbers') !== false) {
			if ((int)$app->get('POST.digits') > 0)
				$digits = (int)$app->get('POST.digits');
			else
				$error = 'Wrong digits to rand';

			if (($attempts = validate::filter('int', $app->get('POST.attempts'), ['min' => 1])) === false)
				$error = 'Wrong answer count';

			if ($number_list = $app->get('POST.number_list'))
				$number_list_arr = explode(PHP_EOL, $number_list);

			if (!empty($number_list_arr))
				$numbers = validate::filter_array('int_no_zero', $number_list_arr);
			else
				$error = 'Wrong numbers';
		}

		if(!$error)
			$randomized = $this->randomize($numbers, $digits, $attempts);

		$app->mset([
			'content' => 'randomize_numbers.html',
			'error' => $error,
			'page_title' => 'Randomize numbers',
			'randomized' => implode("\n", $randomized),
			'number_list' => implode("\n", $numbers),
			'digits' => $digits,
			'attempts' => $attempts,
			'fill_data' => __('errors.fill_data'),
		]);

		$this->render();
	}

	function randomize($numbers = [], $digits = 2, $attempts = 3) {
		$randomized = $new = [];
		if(empty($numbers))
			return [];

		$all_numbers = $numbers;

		foreach ($numbers as $num) {
			if(strlen($num) > $digits+1) {
				$new = $this->random_unique($digits, $num, $numbers, $attempts);
				if(!empty($new)) {
					array_push($randomized, ...$new);
					$all_numbers = array_merge($all_numbers, $new);
				}
			}
		}

		return $this->mt_shuffle($randomized);
	}

	function mt_shuffle($array) {
		$randArr = [];
		$arrLength = count($array);

		while (count($array)) {
			$randPos = mt_rand(0, --$arrLength);
			$randArr[] = $array[$randPos];
			array_splice($array, $randPos, ($randPos == $arrLength ? 1 : $randPos - $arrLength));
		}

		return $randArr;
	}

	function random($digits = 2) {
		$ret = '';
		for ($i = 0; $i < $digits; $i++)
			$ret.= mt_rand(0, 9);

		return $ret;
	}

	function random_unique($digits = 2, $number, $arr_compared, $limit = 1) {
		$limit_tries = 300;
		$l = 0;
		$number_prefix = substr($number,0, strlen($number)-$digits);
		$return = [];
		while (true) {
			$l++;
			$sufix = '';
			for ($i = 0; $i < $digits; $i++)
				$sufix .= mt_rand(0, 9);

			if(!in_array($number_prefix . $sufix, $arr_compared) && !in_array($number_prefix . $sufix, $return)) {
				$return[] = $number_prefix . $sufix;
				if(count($return) >= $limit)
					break;
			}

			if($l >= $limit_tries)
				return $return;
		}

		return $return;
	}
}

