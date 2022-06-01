<? /*
 api for calls
 */

namespace controllers;

use helpers\arr;
use helpers\auth;
use helpers\html;
use helpers\validate;
use ravan\log;
use ravan\ravan;

class api  extends \application{

	var $numbers, $data = [];

	var $db_numbers = 'otp_numbers';
	var $db_number_requests = 'otp_number_requests';
	var $db_ranges = 'otp_ranges';

	var $time_limit = 60*60; //60 min

	var $code_active = false;

	var $log_file = '/tmp/ravan/api.log';

	var $cache_key = 'gen_numbers_';
	var $generate_range_id = 1;
	protected $client_id = 1;

	public function __construct($cli = false) {
		if($cli === true)
			return false;

		$this->cache = \Cache::instance();

		$this->app = \Base::instance();
		$this->app->config('otp.ini');

		$this->db = \Base::instance()->get('db');

		preg_match( "/\/.*(json|plain|hquery)$/", $_SERVER['REQUEST_URI'], $matches);

		if(!empty($matches[1]) && in_array($matches[1], ['json', 'plain', 'hquery']))
			$this->answer_type = $matches[1];
		else
			$this->answer_type = 'json';

		$request = str_replace('/' . $this->answer_type, '',  $_SERVER['REQUEST_URI']);
		$request = str_replace('/api/', '', $request);

		preg_match( "/^([a-z_0-9]+)\/?.*$/", $request, $matches2);

		if(!in_array($matches2[1], get_class_methods($this)))
			$this->error('requested method not found');

//		$this->log = new \Log('/tmp/ravan/api.log');
//		$this->log = log::instance('/tmp/ravan/api.log');
//		$this->log->add('test');
	}

	protected function generate_number($ranges) {
		$tier = mt_rand(0, count($ranges)-1);
		$nt = $ranges[$tier];
		return ['number' => mt_rand($nt['start'], $nt['end']), 'range_id' => $nt['id']];
	}

	public function get_number() {
		$settings = $this->import_client_settings($this->client_id);
		$ranges_model = new \ranges_model();
		$numbers_model = new \numbers_model();

		$list_numbers = arr::map_id_nested($ranges_model->get_number_list(1, null, null, 1, 0), 'group_id');

		if($settings['numbers_from_list'] && !empty($list_numbers)) {
			$current_list_numbers = $list_numbers[array_rand($list_numbers)];
			$number_data = $current_list_numbers[array_rand($current_list_numbers)];
			$number_id = $numbers_model->save_number($number_data['number'], $number_data['range_id'], 2);
			$generated = ['number' => $number_data['number'], 'range_id' => $number_data['range_id']];
			$ranges_model->update_number_from_list($number_data['number_id'], 0);
		}
		else {
			$ranges = $this->get_ranges();
			if (!empty($ranges))
				$generated = $this->generate_number($ranges);
			if (!empty($generated)) {
				$number_id = $numbers_model->save_number($generated['number'], $generated['range_id'], 1); //type 1 generate from range
			}
		}

		if (!empty($generated)) {
			$this->log('get_number: ' . '+' . $generated['number']);
			$this->answer(true, ['number' => '+' . $generated['number']]);
		}
		else
			$this->answer(false, []);

	}

	private function set_used_numbers($number) {
		$old_numbers = $this->get_user_numbers();
		$old_numbers[] = $number;
		$this->cache->set($this->cache_key, $old_numbers, TTL_MONTH);
	}

	private function get_user_numbers() {
		return $this->cache->get($this->cache_key);
	}


	public function test() {
		$used_numbers = $this->get_user_numbers();
		while (true) {
			$number = mt_rand(11, 99);
			if (!in_array($number, $used_numbers))
				break;
		}
		$this->set_used_numbers($number);
	}

	public function check_generated() {
		$used_numbers = $this->get_user_numbers();
		foreach ($used_numbers as $number) {
			echo $number . '<br>';
		}
		echo 'total:' . count($used_numbers) . '<br>';
		echo 'total_numbs:' . count($this->numbers_to_generate_from) . '<br>';
	}


	public function get_status(\Base $app, $params) {
		if($number = validate::filter('int', $params['param1'])) {
			$time = time() - $this->time_limit;

			$res = $this->db->exec("select * from {$this->db_numbers} where number = :number and date >= {$time} order by date asc limit 1", [':number' => $number]);

			$this->db->exec("insert into {$this->db_number_requests} set number_id = {$res[0]['id']}, date = " . time());

			if($this->code_active) {
				if ($res[0]['status'] == 0)
					$status = ['number_status' => 'in_progress'];
				elseif ($res[0]['status'] == 1)
					$status = ['number_status' => 'ok', 'code' => $res[0]['code']];
				elseif ($res[0]['status'] == 2)
					$status = ['number_status' => 'ok', 'code' => $res[0]['code']];
				elseif ($res[0]['status'] >= 3)
					$status = ['number_status' => 'error'];
			}
			else  {
				if (!empty($res[0])) {
					if ($res[0]['status'] == 0)
						$status = ['number_status' => 'in_progress'];
					elseif ($res[0]['status'] == 1)
						$status = ['number_status' => 'retry'];
					elseif ($res[0]['status'] >= 2)
						$status = ['number_status' => 'error'];
				} else
					$status = ['number_status' => 'error'];
			}

			$this->log('get_status:' . $number . '; '. print_r($status, 1));

			$this->answer(true, array_merge(['number' => $number], $status));
		}

		$this->error('wrong number');
	}

	private function answer($status = true, array $data) {
		$data = array_merge(['response_status' => $status == true ? 'ok' : 'error'], $data);

		if($this->answer_type == 'json'){
			echo json_encode($data);
			die;
		}
		elseif($this->answer_type == 'hquery'){
			echo http_build_query($data);
			die;
		}
		die;
	}

	private function error($error_message = 'not found') {
		$this->answer(false, ['error' => $error_message]);
	}

	function log($data, $append = true) {
		return file_put_contents($this->log_file, date('Y-m-d H:i:s ') . $data . PHP_EOL,FILE_APPEND);
	}

	function map_stats() {
		$ranges = $this->get_ranges();
		$short_ranges = arr::map_key_val($ranges, 'short_code', 'id');
		$numbers = $this->db->exec("SELECT * from {$this->db_numbers} where range_id is null ");
		$count = 0;
		foreach ($numbers as $number) {
			$range_id = $this->universal_phone_code_searcher($number['number'], $short_ranges);
			$this->db->exec("update {$this->db_numbers} set range_id = {$range_id} where id = {$number['id']}");
			$count++;
		}
		echo 'updated: '. $count;
	}

	function get_ranges($status = 1) {
		$ranges = $this->db->exec("select * from {$this->db_ranges} where status >= :status", ['status' => $status]);

		for ($i = 0; $i < count($ranges); $i++) {
			$ranges[$i]['short_code'] = substr($ranges[$i]['start'], 0, 3);
			$ranges[$i]['partner'] = $this->app->get('partners.' . $ranges[$i]['partner_id']);
		}
		return $ranges;
	}


//	protected function get_short_ranges() {
//		$ranges = $this->db->exec("select * from {$this->db_ranges}");
//
//		$a = [1, 2, 3, 4, 5];
//		$b = array_map(function($n) {return ($n * $n * $n); }, $a);
//		print_r($b);
////		arr::map_id($this->get_ranges(), '')
//
//	}

	protected function universal_phone_code_searcher($key, &$array) {
		while (strlen($key) > 0) {
			if (array_key_exists($key, $array)) {
				return $array[$key];
			}
			$key = substr($key, 0, -1);
		}
		return false;
	}
//
//	function proxy_check() {
////		html::curl2('https://2ip.ru', )
//	}

//	function show_tries(\Base $app, $params) {
////		\helpers\auth::require_login();
//		if (!($limit = validate::filter('int', $params['param1'])))
//			$limit = 200;
//
//		$pdo_params = [':limit' => $limit];
//
//		$sql_params = [];
//		if (($partner_id = validate::filter('int_no_zero', $app->get('GET.partner_id'))) ) {
//			$pdo_params[':partner_id'] = $partner_id;
//			$sql_params[] = 'ran.partner_id  = :partner_id';
//		}
//
//		$str_params = '';
//		if(!empty($sql_params))
//			$str_params = ' where ' .implode(' and ', $sql_params);
//
//		//SELECT *, n.id as nid, req.id as req_id, FROM_UNIXTIME(req.date), req.date as req_date, n.date as origin_date from otp_numbers as n left join otp_number_requests as req on req.number_id = n.id left join otp_ranges as ran on ran.id = n.range_id order by n.id desc, req.id desc limit 200;
//		$numbers = $this->db->exec("SELECT *, req.date as req_date, n.date as origin_date from {$this->db_numbers} as n left join {$this->db_number_requests} as req on req.number_id = n.id left join otp_ranges as ran on ran.id = n.range_id {$str_params} order by n.id desc, req.id desc limit :limit;", $pdo_params);
//		$ranges = arr::map_key_val($this->get_ranges(), 'short_code', 'partner_id');
//
//		$counters = ['req_date_status' => 0, 'req_date_call' => 0];
//
//		for ($i = 0; $i < count($numbers); $i++) {
//			$partner = $this->universal_phone_code_searcher($numbers[$i]['number'], $ranges);
//			$numbers[$i]['partner'] = $app->exists('partners.' . $partner) ? $app->get('partners.' . $partner) : 'partner id not found: ' . $partner;
//			$numbers[$i]['country'] = $app->exists('countries.' . $numbers[$i]['country_id']) ? $app->get('countries.' . $numbers[$i]['country_id']) : '';
//			if($numbers[$i]['req_date'] == '') {
//				$counters['req_date_status']++;
//				$counters['req_date_call']++;
//			}
//
//
//		}
//
//		$partners = $app->get('partners');
//		$partners[0] = 'All';
//
//		$app->mset([
//			'content' => 'otp.html',
//			'app' => $app,
//			'data' => $numbers,
//			'ranges' => $this->get_ranges(),
//			'partners_data' => $partners,
//			'countries' => $app->get('countries'),
//		]);
//
//		$data = explode("\n", $this->app->get('db')->log());
//		$count_data = count($data)-1;
//		$data[] = 'total sql:' . $count_data;
//		$this->app->set('profiler', $data);
//
//		echo \View::instance()->render($app->get('default_template_file'));
////
////		$this->render();
//	}
}