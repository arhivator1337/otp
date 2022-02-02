<? /*
 api for calls
 */

namespace controllers;

use helpers\arr;
use helpers\auth;
use helpers\html;
use helpers\validate;
use models\Model;
use ravan\log;
use ravan\ravan;

class api_v2 extends \application{

//	var $numbers, $data = [];
//	var $db_numbers = 'otp_numbers';
//	var $db_number_requests = 'otp_number_requests';
//	var $db_ranges = 'otp_ranges';

	var $time_limit = 60*60; //60 min
	public $code_active = false;
	public $log_file = '/tmp/ravan/api_v2.log';
	var $cache_key = 'gen_numbers_';
	public $client_id = 1;

	public function __construct($cli = false) {
		if($cli === true)
			return false;

		$this->cache = \Cache::instance();

		$this->app = \Base::instance();
		$this->app->config('otp.ini');

		preg_match( "/\/.*(json|plain|hquery)$/", $_SERVER['REQUEST_URI'], $matches);

		if(!empty($matches[1]) && in_array($matches[1], ['json', 'serialize', 'hquery']))
			$this->answer_type = $matches[1];
		else
			$this->answer_type = 'json';
		$request = str_replace('/' . $this->answer_type, '',  $_SERVER['REQUEST_URI']);
		$req = explode('/', $request);

		$api_key = $req[2];
		unset($req[0], $req[1], $req[2]);

		if(!empty($api_key) && !in_array($api_key, $this->app->get('api_keys')))
			$this->error('wrong api key');

//		preg_match( "/^([a-z_0-9]+)\/?.*$/", $request, $matches2);

		if(!in_array($req[3], get_class_methods($this)))
			$this->error('requested method not found');

		$this->db = \Base::instance()->get('db');
		$this->message = new \controllers\system\messages();

//		$this->log = new \Log('/tmp/ravan/api.log');
//		$this->log = log::instance('/tmp/ravan/api.log');
//		$this->log->add('test');
	}

//	public function generate_api_key() {
//		echo $this->generateRandomString();
//	}

	function __desctruct() {
		die;
	}

	private function profiler() {
		$data = explode("\n", $this->app->get('db')->log());
	}

	private function generateRandomString($length = 16) {
		return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_', ceil($length/strlen($x)) )),1,$length);
	}

	protected function generate_number($ranges, $no_tier = false) {
		if ($no_tier == false) {
			$tier = mt_rand(0, count($ranges) - 1);
			$nt = $ranges[$tier];
		}

		return ['number' => mt_rand($nt['start'], $nt['end']), 'range_id' => $nt['id'], 'type' => 1, 'country_id' => $nt['country_id']];
	}

	public function get_data() {
		$ranges_model = new \ranges_model();
		$numbers_model = new \numbers_model();
		$settings = $this->import_client_settings($this->client_id);

		$ranges = $ranges2 = [];

		if($settings['numbers_from_ranges'])
			$ranges = $ranges_model->get_random_range(1);

		if ($settings['numbers_from_list']) //type 2 get from list
			$ranges2 = arr::map_id_nested($ranges_model->get_number_list(1, null, null, 1, 0), 'group_id');

		$merged = array_merge($ranges, $ranges2);

		$random = $merged[mt_rand(8, count($merged)-1)];

		if(is_array($random[0])) {
			$rand = $random[mt_rand(0, count($random) - 1)];
			$generated = array_merge($rand, ['type' => 2, 'country_id' => $rand['country_id']]);
		}
		else
			$generated = $this->generate_number($ranges); //type 1 generate from range

		if(empty($generated))
			$this->message->add('generation_error', 'api_v2 number generation');

		$proxy = $this->get_proxy($generated['country_id']);
		if($proxy['error']) {
			$error = $proxy['error'];
			$this->message->add('proxy_error', 'api_v2 proxy generation', print_r($proxy, 1));
		}
//
		if(!$error) {
			$names = new \controllers\system\name_generator();

			if(!($tier = $this->app->get('name_country_group')[$generated['country_id']])) //['group'];
				$this->message->add('generation_error', 'api_v2 name group', ' country_id:' . $generated['country_id'] . ' has no name_country_group');

			$name = $names->generate_name($tier, 'all', 1)[0];
			$name_arr = explode(' ', $name);

			$nickname = $names->generate_nickname(1, $name_arr[mt_rand(0,1)])[0];
			$user_agent = $names->generate_user_agents();
			$data = [
				'number' => '+' . $generated['number'],
				'proxy' => "{$proxy['login']}:{$proxy['pass']}@{$proxy['ip']}:{$proxy['port']}",
				'name' => $name,
				'nickname' => $nickname,
				'nickname2' => $nickname . mt_rand(0, 999),
				'user_agent' => $user_agent,
			];

			$extra = [
				'proxy_is_mobile' => $proxy['mobile'],
				'proxy_is_proxy' => $proxy['proxy'],
				'proxy_is_hosting' => $proxy['hosting'],
			];

			$number_id = $numbers_model->save_number($generated['number'], $generated['range_id'], $generated['type']);
			(new \numbers_data_model())->save_number_data($number_id, $proxy['id'], $proxy['query'], $proxy['countryCode'], $user_agent, "{$name} ({$tier})", $nickname, serialize($extra));

			if($generated['type'] == 2) //list
				$ranges_model->update_number_from_list($number_id, 0);
			/*save all the data to otp_numbers_data
				*/

			$this->profiler();

			$this->log('get_number: ' . '+' . print_r($data, 1));
			$this->answer(true, $data);
		}
		else {
			$this->answer(false, ['error' => $error]);
		}

	}

	protected function get_proxy($country_id = false) {
		$proxy = $this->load_proxy($country_id);
		if(isset($proxy['error']))
			return $proxy;

		$proxy_data = html::check_proxy_real_ip($proxy);

		$countries = array_flip($this->app->get('countries'));
		if(!empty($proxy_data)) {
			unset($proxy_data['status']);
			if (!($country_id = $countries[$proxy_data['country']]))
				return array_merge($proxy, $proxy_data, ['message' => 'could not recognize country']);
		}
		return array_merge($proxy, $proxy_data);
	}

	protected function load_proxy($country_id = false) { //US
		if($country_id)
			$country[] = $country_id;

		$country[] = $default_country = $this->app->get('proxy_default_country_id');
		$proxies = \proxy_model::instance()->get_proxy($country, 1, time());

		if(count($proxies) == 1)
			return $proxies[0];
		elseif(count($proxies) == 0)
			return ['error' => 'no_proxy'];

		if($country_id !== false) {
			$c_proxy = arr::search_key_vals($proxies, 'country_id', $country_id);

			if (count($c_proxy) > 0)
				return $c_proxy[mt_rand(0, count($c_proxy) - 1)];
		}

		return $proxies[mt_rand(0, count($proxies)-1)];
	}

	public function set_status(\Base $app, $params) {
		if ($number = validate::filter('int', $params['param2'])) {
			//@todo save status
			$time = time() - $this->time_limit;
			$this->log('set_status:' . $number . '; status: ' . $params['param3'], 1);
			$this->answer(true, ['number' => $number, 'status' => 'saved']);
		}
		$this->error('wrong number');
	}

//	public function get_number() {
//		if($this->generate_from_list) {
//
//			$used_numbers = $this->get_user_numbers()?: [];
//			while (true) {
//				$number = $this->numbers_to_generate_from[mt_rand(0, count($this->numbers_to_generate_from) - 1)];
//				if (!in_array($number, $used_numbers))
//					break;
//			}
//			$this->set_used_numbers($number);
//
//			$generated = ['number' => $number, 'range_id' => $this->generate_range_id];
//		}
//		else {
//			$ranges = $this->get_ranges();
//			$generated = $this->generate_number($ranges);
//		}
//
//		$model = new \DB\SQL\Mapper($this->db, $this->db_numbers);
//		$model->number = $generated['number'];
//		$model->date = time();
//		$model->status = 0;
//		$model->range_id = $generated['range_id'];
//		$model->save();
//
//		$this->log('get_number: ' . '+' . $generated['number']);
//		$this->answer(true, ['number' => '+' . $generated['number']]);
//	}

//	private function set_used_numbers($number) {
//		$old_numbers = $this->get_user_numbers();
//		$old_numbers[] = $number;
//		$this->cache->set($this->cache_key, $old_numbers, TTL_MONTH);
//	}
//
//	private function get_user_numbers() {
//		return $this->cache->get($this->cache_key);
//	}


//	public function check_generated() {
//		$used_numbers = $this->get_user_numbers();
//		foreach ($used_numbers as $number) {
//			echo $number . '<br>';
//		}
//		echo 'total:' . count($used_numbers) . '<br>';
//		echo 'total_numbs:' . count($this->numbers_to_generate_from) . '<br>';
//	}


	public function get_status(\Base $app, $params) {
		if($number = validate::filter('int', $params['param2'])) {
			$number_model = new \numbers_model();
			$res = $number_model->get_status($number, time() - $this->time_limit);
			if($res[0]['id']) {
				$number_model->insert_num_request($res[0]['id']);

				if ($this->code_active) {
					if ($res[0]['status'] == 0)
						$status = ['number_status' => 'in_progress'];
					elseif ($res[0]['status'] == 1)
						$status = ['number_status' => 'ok', 'code' => $res[0]['code']];
					elseif ($res[0]['status'] == 2)
						$status = ['number_status' => 'ok', 'code' => $res[0]['code']];
					elseif ($res[0]['status'] >= 3)
						$status = ['number_status' => 'error'];
				} else {
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

				$this->log('get_status:' . $number . '; ' . print_r($status, 1));

				$this->answer(true, array_merge(['number' => $number], $status));
			}
		}

		$this->error('wrong number');
	}

	private function answer($status = true, array $data) {
		$data = array_merge(['response_status' => $status == true ? 'ok' : 'error', 'time_spend' => round(microtime(true) - $this->app->get('start_time'), 3)], $data);

		if($this->answer_type == 'json'){
			echo json_encode($data);
		}
		elseif($this->answer_type == 'hquery'){
			echo http_build_query($data);
		}
		elseif($this->answer_type == 'serialize'){
			echo serialize($data);
		}
		$this->__desctruct();
	}

	private function error($error_message = 'not found') {
		$this->answer(false, ['error' => $error_message]);
	}

	function log($data, $append = true) {
		return file_put_contents($this->log_file, date('Y-m-d H:i:s ') . $data . PHP_EOL,FILE_APPEND);
	}

//	function map_stats() {
//		$ranges = $this->get_ranges();
//		$short_ranges = arr::map_key_val($ranges, 'short_code', 'id');
//		$numbers = $this->db->exec("SELECT * from {$this->db_numbers} where range_id is null ");
//		$count = 0;
//		foreach ($numbers as $number) {
//			$range_id = $this->universal_phone_code_searcher($number['number'], $short_ranges);
//			$this->db->exec("update {$this->db_numbers} set range_id = {$range_id} where id = {$number['id']}");
//			$count++;
//		}
//		echo 'updated: '. $count;
//	}

//	function get_ranges($status = 1) {
//		$ranges = $this->db->exec("select * from {$this->db_ranges} where status >= :status", ['status' => $status]);
//
//		for ($i = 0; $i < count($ranges); $i++) {
//			$ranges[$i]['short_code'] = substr($ranges[$i]['start'], 0, 3);
//			$ranges[$i]['partner'] = $this->app->get('partners.' . $ranges[$i]['partner_id']);
//		}
//		return $ranges;
//	}


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