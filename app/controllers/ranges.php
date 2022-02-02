<?php
namespace controllers;

use controllers\system\name_generator;
use helpers\arr;
use helpers\auth;
use helpers\html;
use helpers\validate;
use models\Model;
use PDO;

class ranges extends \controllers\Controller {

	public function __construct() {
		parent::__construct();
		$this->app->set('breadcrumb_no_default_bread', 1);
	}

	public function index(\Base $app, $param) {
		$page = false;
//		if($param['param1'] > 0)
//			$page = $param['param1'];

//		$data = (new \ranges_model())->get_ranges(null, 1);
//		for ($i = 0; $i < count($data); $i++) {
//			$data['country_id']
//		}

		$this->add_main_button('Add Range', '/ranges/ranges_create');
		$this->add_breadcrumb();

		$app->mset([
			'content' => 'ranges.html',
			'page' => $page,
			'countries' => $this->app->get('countries'),
			'data' => (new \ranges_model())->get_ranges_new() ,
			'data_list_groups' => (new \ranges_model())->get_number_list_groups() ,
		]);

		$this->render();
	}


	public function range_create(\Base $app, $params) {
		$this->range_edit(true);
	}

	public function range_edit($add) {
		$app = \Base::instance();

		$this->add_breadcrumb();

		if($app->get('GET.message') == 'created')
			$toast_message = __('mess.created');

		$params = $app->get('PARAMS');

		$ranges_model = new \ranges_model();

		$page_title = 'Add';
		$bread = 'Add';

		if($add !== true) {
			if (($id = (int) $params['param1']) > 0)
				$ranges_model->model->load(['id=?', $id]);
			else
				$this->render_error(500);

			$page_title = 'Edit: ' . $ranges_model->model->start;
			$bread = 'Edit';

			if (!$ranges_model->model->loaded())
				$error = ___('title.not_found');
		}

		if(!$error) {
			if ($app->get('SERVER.REQUEST_METHOD') == 'POST') {
				if ($app->get('POST.start') > 0 && $app->get('POST.end') > 0) {
					if (!$error) {
						$ranges_model->model->start = validate::filter('int', $app->get('POST.start')) ? : 0;
						$ranges_model->model->end = validate::filter('int', $app->get('POST.end')) ? : 0;

						$ranges_model->model->status = validate::filter('0/1', $app->get('POST.status')) ? : 0;
						if($add === true && $app->get('POST.country_id') > 0 && $app->get('POST.partner_id') > 0 ) {
							$ranges_model->model->partner_id = validate::filter('int', $app->get('POST.partner_id')) ?: 0;
							$ranges_model->model->country_id = validate::filter('int', $app->get('POST.country_id')) ?: 0;
						}

						$ranges_model->model->save();
						if($add === true && !$error)
							$app->reroute(\helpers\html::url('/ranges/range_edit/' . $ranges_model->model->id . '?message=created'));
						$toast_message = ___('mess.saved');
					}
					else
						$error = 'something went wrong';
				}
				else
					$error = 'something went wrong';
			}

			$this->add_breadcrumb($bread);

			$app->mset([
				'data' => $ranges_model->model->cast(),
			]);
		}

		$app->mset([
			'content' => 'range_edit.html',
			'error' => $error,
			'page_title' => $page_title,
			'toast_message' => $toast_message,
			'countries' => $this->app->get('countries'),
			'add' => $add,
		]);

		$this->render();
	}

//	function show_tries(\Base $app, $params) {
////		\helpers\auth::require_login();
//
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
////		$model = new \otp_numbers_model;
////		$model->get_all_stats($limit);
//
//		//SELECT *, n.id as nid, req.id as req_id, FROM_UNIXTIME(req.date), req.date as req_date, n.date as origin_date from otp_numbers as n left join otp_number_requests as req on req.number_id = n.id left join otp_ranges as ran on ran.id = n.range_id order by n.id desc, req.id desc limit 200;
//		$numbers = $app->db->exec("SELECT *, req.date as req_date, n.date as origin_date from {$this->db_numbers} as n left join {$this->db_number_requests} as req on req.number_id = n.id left join otp_ranges as ran on ran.id = n.range_id {$str_params} order by n.id desc, req.id desc limit :limit;", $pdo_params);
//		$ranges = arr::map_key_val($this->get_ranges(), 'short_code', 'partner_id');
//
//		for ($i = 0; $i < count($numbers); $i++) {
//			$partner = $this->universal_phone_code_searcher($numbers[$i]['number'], $ranges);
//			$numbers[$i]['partner'] = $app->exists('partners.' . $partner) ? $app->get('partners.' . $partner) : 'partner id not found: ' . $partner;
//			$numbers[$i]['country'] = $app->exists('countries.' . $numbers[$i]['country_id']) ? $app->get('countries.' . $numbers[$i]['country_id']) : '';
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
////		echo \View::instance()->render($app->get('default_template_file'));
//
//		$this->render();
//	}
//
//	public function get(\Base $app, $param) {
//		$page = false;
//		if($param['param1'] > 0)
//			$page = $param['param1'];
//
//		$param = $this->validate_form($app->get('POST'), true);
//
//		$param['date_start'] = strtotime($param['date_start']);
//		$param['date_finish'] = strtotime($param['date_finish']);
//
//
//		$data = \numbers_model::instance()->get_all_stats($param['limit'] > 0 ? (int) $param['limit'] : 100, $page, $param);
//
//		$ranges = arr::map_key_val(\ranges_model::instance()->get_ranges(), 'short_code', 'partner_id');
//
//		for ($i = 0; $i < count($data); $i++) {
//			$data[$i]['date'] = date($app->get('date_template'), $data[$i]['date']);
//			$partner = \helpers\utils::universal_phone_code_searcher($data[$i]['number'], $ranges);
//			$data[$i]['partner'] = $app->exists('partners.' . $partner) ? $app->get('partners.' . $partner) : 'partner id not found: ' . $partner;
//			$data[$i]['country'] = $app->exists('countries.' . $data[$i]['country_id']) ? $app->get('countries.' . $data[$i]['country_id']) : '';
//			$data[$i]['origin_date'] = date($app->get('date_template'), $data[$i]['origin_date']);
//			$data[$i]['req_date'] = date($app->get('date_template'), $data[$i]['req_date']);
//		}
//
//		echo html::to_json($data, $this->profiler());
//		die;
//	}
//
//	protected function validate_form($data, $type = false)  {
//		$data['partner_id'] = validate::filter_array('int', $data['partner_id']);
//		$data['limit'] = validate::filter('int', $data['limit']);
//		$data['date_start'] = validate::filter('date', $data['date_start']);
//		$data['date_finish'] = validate::filter('date', $data['date_finish']);
//		$data['unique_numbers'] = validate::filter('int', $data['unique_numbers']);
//
////		$data['comparison_key'] = (in_array($data['comparison_key'], $this->comparison_keys) ? $data['comparison_key'] : false);
////		$data['status'] = validate::filter_array('in_array',$data['status'], $this->statuses);
//
//		return $data;
//	}

	public function country_name_relation(\Base $app, $param) {
		$names = new name_generator();
		$data = [];

		$countries = $app->get('countries');

		foreach ($app->get('name_country_group') as $country_id => $group) {
			$name = $names->generate_name($group, 'all', 1)[0];
			$name_arr = explode(' ', $name);
			$data[] = [
				'country' => $countries[$country_id] ?: $country_id,
				'group' => $group,
				'name' => $name,
				'nickname' => $names->generate_nickname(1, $name_arr[mt_rand(0,1)])[0],
			];
		}

		$app->mset([
			'content' => 'country_name_relation.html',
			'countries' => $app->get('countries'),
			'name_country_group' => $app->get('name_country_group'),
			'data' => $data,
		]);

		$this->render();
	}


}