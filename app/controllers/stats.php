<?php
namespace controllers;

use controllers\system\name_generator;
use helpers\arr;
use helpers\auth;
use helpers\html;
use helpers\validate;
use models\Model;
use PDO;

class stats extends \controllers\Controller {

	public function __construct() {
		parent::__construct();
		$this->app->set('breadcrumb_no_default_bread', 1);
	}

	public function index(\Base $app, $param) {
		$page = false;
		if($param['param1'] > 0)
			$page = $param['param1'];

		$data = $this->validate_form($app->get('GET'), false);

		$app->mset([
			'content' => 'stats.html',
//			'servers' => (new \server_model())->get_all_arr($this->client_id),
			'page' => $page,
//			'comparison_keys' => $this->comparison_keys,
//			'tasks' => (new \task_model())->get_all_arr($this->client_id),
//			'statuses' => $this->statuses,
			'pagination_url' => '/stats/get/',
			'data' => $data,
		]);

		$this->render();
	}

//	public function daily_by_hour_ajax(\Base $app, $params) {
//		$app->mset([
//			'content' => 'blocks/loadman.html',
//			'data' => $data = $this->daily_by_hour(),
//		]);
//
//		$this->render();
//	}


	function show_tries(\Base $app, $params) {
//		\helpers\auth::require_login();

		if (!($limit = validate::filter('int', $params['param1'])))
			$limit = 200;

		$pdo_params = [':limit' => $limit];

		$sql_params = [];
		if (($partner_id = validate::filter('int_no_zero', $app->get('GET.partner_id'))) ) {
			$pdo_params[':partner_id'] = $partner_id;
			$sql_params[] = 'ran.partner_id  = :partner_id';
		}

		$str_params = '';
		if(!empty($sql_params))
			$str_params = ' where ' .implode(' and ', $sql_params);

//		$model = new \otp_numbers_model;
//		$model->get_all_stats($limit);

		//SELECT *, n.id as nid, req.id as req_id, FROM_UNIXTIME(req.date), req.date as req_date, n.date as origin_date from otp_numbers as n left join otp_number_requests as req on req.number_id = n.id left join otp_ranges as ran on ran.id = n.range_id order by n.id desc, req.id desc limit 200;
		$numbers = $app->db->exec("SELECT *, req.date as req_date, n.date as origin_date from {$this->db_numbers} as n left join {$this->db_number_requests} as req on req.number_id = n.id left join otp_ranges as ran on ran.id = n.range_id {$str_params} order by n.id desc, req.id desc limit :limit;", $pdo_params);
		$ranges = arr::map_key_val($this->get_ranges(), 'short_code', 'partner_id');

		for ($i = 0; $i < count($numbers); $i++) {
			$partner = $this->universal_phone_code_searcher($numbers[$i]['number'], $ranges);
			$numbers[$i]['partner'] = $app->exists('partners.' . $partner) ? $app->get('partners.' . $partner) : 'partner id not found: ' . $partner;
			$numbers[$i]['country'] = $app->exists('countries.' . $numbers[$i]['country_id']) ? $app->get('countries.' . $numbers[$i]['country_id']) : '';
		}

		$partners = $app->get('partners');
		$partners[0] = 'All';

		$app->mset([
			'content' => 'otp.html',
			'app' => $app,
			'data' => $numbers,
			'ranges' => $this->get_ranges(),
			'partners_data' => $partners,
			'countries' => $app->get('countries'),
		]);

		$data = explode("\n", $this->app->get('db')->log());
		$count_data = count($data)-1;
		$data[] = 'total sql:' . $count_data;
		$this->app->set('profiler', $data);

//		echo \View::instance()->render($app->get('default_template_file'));

		$this->render();
	}

	public function get(\Base $app, $param) {
		$page = false;
		if($param['param1'] > 0)
			$page = $param['param1'];

		$param = $this->validate_form($app->get('POST'), true);

		$param['date_start'] = strtotime($param['date_start']);
		$param['date_finish'] = strtotime($param['date_finish']);


		$data = \numbers_model::instance()->get_all_stats($param['limit'] > 0 ? (int) $param['limit'] : 100, $page, $param);

		$ranges = arr::map_key_val(\ranges_model::instance()->get_ranges(), 'short_code', 'partner_id');

		for ($i = 0; $i < count($data); $i++) {
			$data[$i]['date'] = date($app->get('date_template'), $data[$i]['date']);
			$partner = \helpers\utils::universal_phone_code_searcher($data[$i]['number'], $ranges);
			$data[$i]['partner'] = $app->exists('partners.' . $partner) ? $app->get('partners.' . $partner) : 'partner id not found: ' . $partner;
			$data[$i]['country'] = $app->exists('countries.' . $data[$i]['country_id']) ? $app->get('countries.' . $data[$i]['country_id']) : '';
			$data[$i]['origin_date'] = date($app->get('date_template'), $data[$i]['origin_date']);
			$data[$i]['req_date'] = date($app->get('date_template'), $data[$i]['req_date']);
		}

		echo html::to_json($data, $this->profiler());
		die;
	}

	protected function validate_form($data, $type = false)  {
		$data['partner_id'] = validate::filter_array('int', $data['partner_id']);
		$data['limit'] = validate::filter('int', $data['limit']);
		$data['date_start'] = validate::filter('date', $data['date_start']);
		$data['date_finish'] = validate::filter('date', $data['date_finish']);
		$data['unique_numbers'] = validate::filter('int', $data['unique_numbers']);

//		$data['comparison_key'] = (in_array($data['comparison_key'], $this->comparison_keys) ? $data['comparison_key'] : false);
//		$data['status'] = validate::filter_array('in_array',$data['status'], $this->statuses);

		return $data;
	}
}