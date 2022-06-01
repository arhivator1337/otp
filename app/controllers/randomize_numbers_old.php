<?php
namespace controllers;

use helpers\arr;

set_time_limit(40);
ini_set('memory_limit', '512M');
error_reporting(-1);
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 'on');

ini_set("display_errors", 1);
ini_set("track_errors", 1);
ini_set("html_errors", 1);
error_reporting(E_ALL);

class randomize_numbersOld extends \controllers\Controller {

	var $tbl = 'number_test';
	var $tbl_res = 'number_result';
	var $tbl_number_group = 'otp_number_list_groups';
	var $tbl_number_lists = 'otp_number_lists';

	function __construct() {
		parent::__construct();
	}

	function index(\Base $app, $params) {
		$app->mset([
			'content' => 'randomize_numbers.html',
//			'error' => $error,
//			'message' => $message,
			'page_title' => 'Randomize numbers',
			'fill_data' => __('errors.fill_data'),
		]);

		$this->render();
	}

	function generate(\Base $app, $params) { //from list of called numbers, a lot of numbers could be duplicated
		$_show = $params['param1'];
		$check = !empty($params['param2']) && $params['param2'] == 'check' ? true : false;

		var_dump($check);

		$select_more_then_calls = 5;
		$select_more_then_calls_on_level2 = 1;
		$proportion = true;
		$nums_needed = 5000;
		$first_take_symbols = 8;
		$second_take_symbols = 9;

		$all_numbers = arr::map_key_val($app->db->exec("select distinct number2 from {$this->tbl}"), 'number2', 'number2');
		$ready_numbers = arr::map_key_val($app->db->exec("select parent_number from {$this->tbl_res}"), 'parent_number', 'parent_number');

		$res_numbers = [];

		$data = $app->db->exec("select SUBSTRING(number2, 1, {$first_take_symbols}) AS  num, count(*) as cnt from {$this->tbl} group by num with ROLLUP having count(*) > {$select_more_then_calls}");
		$i = 0;
		$nums = [];
		$nums2 = [];

		foreach ($data as $d) {
			$i++;
			if($_show == 'test') {
				echo $i . ': ';
				echo $d['num'] . ' - ' . $d['cnt'] . '<br>';
			}
			if($d['num'])
				$nums[] = $d['num'];
		}

		$regex = implode('|', $nums);

		if($_show == 'test')
			echo $regex;

		$data2 = $app->db->exec("select SUBSTRING(number2, 1, {$second_take_symbols}) AS  num, number2, count(*) as cnt from number_test  where number2 REGEXP('{$regex}') group by num having count(*) > {$select_more_then_calls_on_level2} order by cnt desc;");
		$proportion_n = floor($nums_needed/count($data2));

		$max = max(array_column($data2, 'cnt'));
		$min = $select_more_then_calls_on_level2;

		echo "<br>TOTAL:". count($data2) . "<br>";
		echo "min:". $min . "<br>";
		echo "max:". $max . "<br>";
//		if($proportion_n > )

		$i = 0;
		foreach ($data2 as $d) {
			if(empty($d['cnt']))
				continue;

			if(!$check) {
				if(isset($ready_numbers[$d['number2']]))
				continue;
			}

			$i++;

			//if($d['cnt'] > )
			$repeat_count = round($proportion_n/($max-$min)*$d['cnt'])+1;
			if($d['cnt']/5 > 2)
				$repeat_count = round($d['cnt']/5);
			else
				$repeat_count = 2;

//			echo "<h2>$repeat_count : $max {$d['cnt']}, {$d['number2']}, {$d['num']}</h2>";
//			echo "<h3>$proportion_n/($max-$min)*{$d['cnt']}</h3>";

			$k = 0;
//			$repeat_count = 0;
			while (true) {
				if(mt_rand(0, 3) == 3 or $k > 20)
					$number = substr($d['number2'], 0, -2) . mt_rand(10, 99);
				else
					$number = substr($d['number2'], 0, -1) . mt_rand(0, 9);

				if($check) {
					$res_numbers[$number] = [$d['cnt'], $d['number2'], $d['num'], $k, $repeat_count];
					$repeat_count--;

//					$this->insert_result($number, $d['number2'], "cnt={$d['cnt']} k={$k} rep = {$repeat_count}");
					if ($repeat_count < 1)
						break;
				}
				else {
					if (!isset($all_numbers[$number])) {
						if (!isset($res_numbers[$number])) {
							$repeat_count--;
							$res_numbers[$number] = [$d['cnt'], $d['number2'], $d['num'], $k, $repeat_count];
							$this->insert_result($number, $d['number2'], "cnt={$d['cnt']} k={$k} rep = {$repeat_count}");
							if ($repeat_count < 1)
								break;
						}
					}
				}
				$k++;
			}
			if($i >= 100)
				break;
		}

		foreach ($res_numbers as $num => $d) {
			echo $num;
			if($_show == 'test')
				echo "  - {$d[0]}  - {$d[1]} {$d[2]} r={$d[4]}";
			echo "<br>";
		}

		if($_show == 'test') {
			echo 'TOTAL:' . count($res_numbers) . '<br>';
			echo '<pre>';
			print_r($this->profiler());
			echo '</pre>';
		}

	}

	function export(\Base $app, $params) { //from list of called numbers, a lot of numbers could be duplicated
		$nl_groups_name = 'argentina 27.04 real numbers 2 last random, exclude from original list ';
		$nl_groups_id = !empty($params['param1']) && (int) $params['param1'] > 0 ? (int) $params['param1'] : 5;
		$nl_groups_status = 0;
		$nl_country_id = !empty($params['param2']) && (int) $params['param2'] > 0 ? (int) $params['param2'] : 3;

		$sql_1 = "INSERT INTO `otp_number_list_groups` (`id`, `partner_id`, `name`, `status`) VALUES
({$nl_groups_id}, 1, '{$nl_groups_name}', {$nl_groups_status});";

		$sql_2_prefix = "INSERT INTO `otp_number_lists` (`id`, `group_id`, `number`, `status`, `country_id`) VALUES ";
		$sql_2_sufix = ';';

		$numbers = $app->db->exec("select * from {$this->tbl_res}");
		$all_nums = [];
		foreach ($numbers as $n) {
			$all_nums[] = "(NULL, {$nl_groups_id}, {$n['number']}, 1, {$nl_country_id})";
		}

		echo '<pre>';
		echo $sql_1;
		echo "\n\n";
		echo $sql_2_prefix . "\n" . implode(",\n", $all_nums) . $sql_2_sufix;

	}

	function generate_from_limited_number(\Base $app, $params) {
		$check = !empty($params['param1']) && $params['param1'] == 'check' ? true : false;

		$random_symbols = 2;

		$data2 = $app->db->exec("select SUBSTRING(number2, 1, LENGTH(number2)-{$random_symbols}) as num, number2 from number_test");
		$i = $k = 1;

		foreach ($data2 as $item) {
			while (true) {
				$k++;
				$number = $item['num'] . $this->random($random_symbols);
				if($number !==  $item['number2'] ) {
					$this->insert_result($number, $item['number2'], "");
					echo "{$number} len:" . strlen($number) . ' original:' . $item['number2'] . ' num:' . $item['num'] . '<br>' ;
					$k=0;
					break;
				}
				if($k >= $random_symbols * 10)
					break;
			}
			$i++;
		}
		echo '<br>Total:' . $i;
	}

	function random($symbols = 2) {
		$ret = '';
		for ($i = 0; $i < $symbols; $i++)
			$ret.= mt_rand(0, 9);

		return $ret;
	}

	function random2($symbols = 2) {
		return mt_rand(0, str_repeat(9, $symbols));
	}

	function show() {
		$len_limit = 12;
		$ready_numbers = $this->app->db->exec("select distinct number from {$this->tbl_res} order by rand()");
		shuffle($ready_numbers);

		foreach ($ready_numbers as $r) {
			if(strlen($r['number']) == 12)
			echo $r['number'] . '<br>';
		}

	}


	function shuffle_assoc(&$array) {
		$keys = array_keys($array);
		shuffle($keys);

		foreach($keys as $key)
			$new[$key] = $array[$key];
		$array = $new;
		return true;
	}

	function insert_result($number, $parent_number, $data ) {
		$this->app->db->exec("insert into {$this->tbl_res} set number = {$number}, parent_number = {$parent_number}, data = '{$data}'");
	}
}

