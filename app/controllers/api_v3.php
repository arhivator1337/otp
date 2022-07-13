<?
namespace controllers;

use helpers\arr;
use helpers\auth;
use helpers\html;
use helpers\validate;
use models\Model;
use ravan\log;
use ravan\ravan;

class api_v3 extends api_v2 {

	private $api_response_type = ['json', 'serialize', 'hquery', 'droid'];

	public function __construct($cli = false) {
		if($cli === true)
			return false;

		$this->cache = \Cache::instance();

		$this->app = \Base::instance();
		$this->app->config('otp.ini');

		preg_match( "/\/.*(" . implode('|', $this->api_response_type) . ")/", $_SERVER['REQUEST_URI'], $matches);

		if(!empty($matches[1]) && in_array($matches[1], $this->api_response_type))
			$this->answer_type = $matches[1];
		else
			$this->answer_type = 'droid';


		$request = str_replace('/' . $this->answer_type, '',  $_SERVER['REQUEST_URI']);
		$request = preg_replace('/(\\?.*)+$/', '', $request); //fix for $_GET requests
		$req = explode('/', $request);

		$this->extra = $this->app->get('GET');

		$api_key = $req[2];
		unset($req[0], $req[1], $req[2]);

		if(!empty($api_key) && !in_array($api_key, $this->app->get('api_keys_v3_only')))
			$this-> error('wrong api key');

		if(!in_array($req[3], get_class_methods($this)))
			$this->error('requested method not found');

		$this->db = \Base::instance()->get('db');
		$this->message = new \controllers\system\messages();
	}

	function __desctruct() {
		die;
	}

	private function profiler() {
		return explode("\n", $this->app->get('db')->log());
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

		if(count($merged) == 0) {
			$error = 'no ranges';
			$this->message->add('generation_error', __function__, 'api_v2 no active ranges');
		}

		if(!empty($merged)) {
			$random = $merged[mt_rand(0, count($merged) - 1)];

			if (is_array($random[0])) {
				$rand = $random[mt_rand(0, count($random) - 1)];
				$generated = array_merge($rand, ['type' => 2, 'country_id' => $rand['country_id']]);
			} else
				$generated = $this->generate_number($ranges); //type 1 generate from range
		}


		if(empty($generated))
			$this->message->add('generation_error', __function__, 'api_v2 number generation');

		if($this->app->get('api_v3_proxy_on')) {
			$proxy = $this->get_proxy($generated['country_id']);
			if ($proxy['error']) {
				$error = $proxy['error'];
				$this->message->add('proxy_error', __function__, 'api_v2 proxy generation', print_r($proxy, 1));
			}
		}

		if(!$error) {
			$names = new \controllers\system\name_generator();

			if(!($tier = $this->app->get('name_country_group')[$generated['country_id']])) //['group'];
				$this->message->add('generation_error', __function__, 'country_id:' . $generated['country_id'] . ' has no name_country_group');


			$name = $names->generate_name($tier, 'all', 1)[0];
			$name_arr = explode(' ', $name);


			$nickname = $names->generate_nickname(1, $name_arr[mt_rand(0,1)])[0];
			$user_agent = $names->generate_user_agents();
			$data = [
				'number' => '+' . $generated['number'],
				'number_country' => $this->app->get('countries_code')[$generated['country_id']],
				'name' => $name,
				'nickname' => $nickname . $this->random_int(2),
				'nickname2' => $nickname . $this->random_int(3),
				'user_agent' => $user_agent,
				'requests_limit' => 1,
			];

			$extra = [];

			if($this->app->get('api_v3_proxy_on')) {
				$data = array_merge($data, [
					'proxy' => "{$proxy['login']}:{$proxy['pass']}@{$proxy['ip']}:{$proxy['port']}",
					'proxy_country' => $proxy['countryCode'],
					'proxy_city' => $proxy['city'],
					'proxy_timezone' => $proxy['timezone'],
				]);

				$extra = [
					'proxy_is_mobile' => $proxy['mobile'],
					'proxy_is_proxy' => $proxy['proxy'],
					'proxy_is_hosting' => $proxy['hosting'],
				];
			}

			$number_id = $numbers_model->save_number($generated['number'], $generated['range_id'], $generated['type']);
			(new \numbers_data_model())->save_number_data($number_id, $proxy['id'], $proxy['query']?:'', $proxy['countryCode']?:'', $user_agent, "{$name} ({$tier})", $nickname, serialize($extra));
			$data['transaction_id'] = $number_id;

			if($generated['type'] == 2) //list
				$ranges_model->update_number_from_list($generated['number_id'], 0);

			$this->profiler();

			$this->log('get_number: ' . '+' . print_r($data, 1));

			$this->answer(true, $data);
		}
		else {
			$this->answer(false, ['error' => $error]);
		}

	}

	public function get_data_list() {
		$ranges_model = new \ranges_model();
		$numbers_model = new \numbers_model();
		$group_id = (int) $this->app->get('PARAMS.param2') ;
		if(!($group_id > 0))
			$error = 'wrong id';

		$settings = $this->import_client_settings($this->client_id);

		$ranges = [];
		if(!$error) {
			$ranges = $ranges_model->get_number_list(0, null, null, 1, 0, $group_id);

			if(count($ranges) == 0) {
				$error = 'no ranges';
				$this->message->add('generation_error', __function__, 'api_v2 no active ranges');
			}
			else  {
				$random = $ranges[mt_rand(0, count($ranges) - 1)];

				if(!empty($ranges))
					$generated = array_merge($random, ['type' => 2, 'country_id' => $random['country_id']]);

				if(empty($generated))
					$this->message->add('generation_error', __function__, 'api_v2 number generation');
			}

			if($this->app->get('api_v3_proxy_on')) {
				$proxy = $this->get_proxy($generated['country_id']);
				if ($proxy['error']) {
					$error = $proxy['error'];
					$this->message->add('proxy_error', __function__, 'api_v2 proxy generation', print_r($proxy, 1));
				}
			}
		}

		if(!$error) {
//			$names = new \controllers\system\name_generator();

//			if(!($tier = $this->app->get('name_country_group')[$generated['country_id']])) //['group'];
//				$this->message->add('generation_error', __function__, 'country_id:' . $generated['country_id'] . ' has no name_country_group');
//

//			$name = $names->generate_name($tier, 'all', 1)[0];
//			$name_arr = explode(' ', $name);
//
//
//			$nickname = $names->generate_nickname(1, $name_arr[mt_rand(0,1)])[0];
//			$user_agent = $names->generate_user_agents();
//			$data = [
//				'number' => '+' . $generated['number'],
//				'number_country' => $this->app->get('countries_code')[$generated['country_id']],
//				'name' => $name,
//				'nickname' => $nickname . $this->random_int(2),
//				'nickname2' => $nickname . $this->random_int(3),
//				'user_agent' => $user_agent,
//				'requests_limit' => 1,
//			];

			$extra = [];
//
//			if($this->app->get('api_v3_proxy_on')) {
//				$data = array_merge($data, [
//					'proxy' => "{$proxy['login']}:{$proxy['pass']}@{$proxy['ip']}:{$proxy['port']}",
//					'proxy_country' => $proxy['countryCode'],
//					'proxy_city' => $proxy['city'],
//					'proxy_timezone' => $proxy['timezone'],
//				]);
//
//				$extra = [
//					'proxy_is_mobile' => $proxy['mobile'],
//					'proxy_is_proxy' => $proxy['proxy'],
//					'proxy_is_hosting' => $proxy['hosting'],
//				];
//			}

			$number_id = $numbers_model->save_number($generated['number'], $generated['range_id'], $generated['type']);
			$user_agent = $name = $tier = $nickname = '';
			(new \numbers_data_model())->save_number_data($number_id, $proxy['id'], $proxy['query']?:'', $proxy['countryCode']?:'', $user_agent, "{$name} ({$tier})", $nickname, serialize($extra));

			$data = [
				'number' => '+' . $generated['number'],
			];
			$data['transaction_id'] = $number_id;

			if($generated['type'] == 2) //list
				$ranges_model->update_number_from_list($generated['number_id'], 0);

			$this->profiler();

			$this->log('get_number: ' . '+' . print_r($data, 1));

			$this->answer(true, $data);
		}
		else {
			$this->answer(false, ['error' => $error]);
		}

	}


	protected function get_proxy($country_id = false) {
		$proxy_data = [];
		$proxy = $this->load_proxy($country_id);
		if(isset($proxy['error']))
			return $proxy;

		if(empty($this->extra['no_proxy_check']))
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

		if($country_id !== false or $country_id !== null) {
			$c_proxy = arr::search_key_vals($proxies, 'country_id', $country_id);

			if (!empty($c_proxy))
				return $c_proxy[mt_rand(0, count($c_proxy) - 1)];
		}

		return $proxies[mt_rand(0, count($proxies)-1)];
	}

	public function set_status(\Base $app, $params) {
		$error = false;
		if ($number = validate::filter('int', $params['param2'])) {
			$status = $params['param3'];
			$statuses = array_flip($app->get('number_request_type'));

			$number_model = new \numbers_model();

			if($statuses[$status] && $status !== NULL) {
				$res = $number_model->get_status($number, time() - $this->time_limit);

				if($res[0]['type'] && $status == 10) //set back status to the number to use it later
					(new \ranges_model)->update_number_from_list_by_number($res[0]['id'], $res[0]['range_id'], 0);

				if($res[0]['id'])
					$number_model->insert_num_request($res[0]['id'], $statuses[$status]);
				else
					$error = 'wrong number';
			} else
				$error = 'wrong status';
		}

		if($error) {
			$this->error('wrong status');
			$this->log('set_status:' . $number . '; wronge status: ' . $params['param3'], 1);
		} else {
			$this->log('set_status:' . $number . '; status: ' . $params['param3'], 1);
			$this->answer(true, ['number' => $number, 'status' => 'saved']);
		}
	}

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
		elseif($this->answer_type == 'droid') {
			$data['number'] = str_replace('+', '', $data['number']);
			if($status)
				echo "ACCESS_NUMBER:{$data['transaction_id']}:{$data['number']}";
			else
				echo "ERROR:" . $data['error'];
		}
		$this->__desctruct();
	}

	private function error($error_message = 'not found') {
		$this->answer(false, ['error' => $error_message]);
	}

	function log($data, $append = true) {
		return file_put_contents($this->log_file, date('Y-m-d H:i:s ') . $data . PHP_EOL,FILE_APPEND);
	}

	protected function universal_phone_code_searcher($key, &$array) {
		while (strlen($key) > 0) {
			if (array_key_exists($key, $array)) {
				return $array[$key];
			}
			$key = substr($key, 0, -1);
		}
		return false;
	}

	function random_int($symbols = 2) {
		$ret = '';
		for ($i = 0; $i < $symbols; $i++)
			$ret.= mt_rand(0, 9);

		return $ret;
	}

}