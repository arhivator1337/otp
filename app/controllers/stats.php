<?php
namespace controllers;

use helpers\arr;
use helpers\html;
use helpers\validate;

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
			'countries' => $app->get('countries'),
			'ranges' => \ranges_model::instance()->get_ranges(),
			'page' => $page,
			'pagination_url' => '/stats/get/',
			'data' => $data,
		]);

		$this->render();
	}
	public function get(\Base $app, $param) {


		if(!($page = validate::filter('int', $param['param1'])))
			$page = false;

		$param = $this->validate_form($app->get('POST'), true);
		$param['limit'] = $param['limit']?:500;

		if($page)
			$param['offset'] = $param['limit']*$page;

		$param['date_start'] = strtotime($param['date_start']);
		$param['date_finish'] = strtotime($param['date_finish']);

		$numbers_to_expose = $param['number'];

		if(!$param['numbers_checker'])
			$param['number'] = [];

		$data = \numbers_model::instance()->get_all_stats($param);

		$ranges = arr::map_key_val(\ranges_model::instance()->get_ranges(), 'short_code', 'partner_id');
		$new_data = [];

		for ($i = 0; $i < count($data); $i++) {
			$arr = [];

			$arr['date'] = date($app->get('date_template'), $data[$i]['date']);
			$partner = \helpers\utils::universal_phone_code_searcher($data[$i]['number'], $ranges);
			$arr['partner'] = $app->exists('partners.' . $partner) ? $app->get('partners.' . $partner) : 'partner id not found: ' . $partner;
			$arr['country'] = $this->universal_phone_code_searcher('34611500659', $app->get('phone_codes_country'));
			$arr['origin_date'] = date($app->get('date_template'), $data[$i]['origin_date']);
			if(!empty($data[$i]['req_date']))
				$arr['req_date'] = date($app->get('date_template'), $data[$i]['req_date']);

			$arr['number'] = $data[$i]['number'];
			$arr['proxy'] = $data[$i]['login'] . ':' . $data[$i]['pass'] . '@' . $data[$i]['ip'] . ':' . $data[$i]['port'];
			$arr['name'] = $data[$i]['name'];
			$arr['nickname'] = $data[$i]['nickname'];

			if(in_array($arr['number'], $numbers_to_expose))
				$arr['numbers_checked'] = 'yes';

			$new_data[] = $arr;
		}

		echo html::to_json($new_data, $this->profiler());
		die;
	}


	protected function universal_phone_code_searcher($key, $array) {
		while (strlen($key) > 0) {
			if (array_key_exists($key, $array)) {
				if($this->app->get('countries.' . $array[$key]))
					return $this->app->get('countries.' . $array[$key]);
			}

			$key = substr($key, 0, -1);
		}
		return false;
	}

	protected function validate_form($data, $type = false)  {
		$data['number'] = validate::filter_array('int_no_zero', preg_split("/\\r\\n|\\r|\\n/", $data['numbers']));
		$data['number_starts'] = validate::filter('int', $data['number_starts']);
		$data['partner_id'] = validate::filter_array('int', $data['partner_id']);
		$data['country_id'] = validate::filter_array('int', $data['country_id']);
		$data['range_id'] = validate::filter_array('int', $data['range_id']);
		$data['limit'] = validate::filter('int', $data['limit']);
		$data['date_start'] = validate::filter('date', $data['date_start']);
		$data['date_finish'] = validate::filter('date', $data['date_finish']);
		$data['unique_numbers'] = validate::filter('0/1', $data['unique_numbers']);
		$data['only_success'] = validate::filter('0/1', $data['only_success']);
		$data['numbers_checker'] = validate::filter('0/1', $data['numbers_checker']);
//		$data['status'] = validate::filter_array('in_array',$data['status'], $this->statuses);

		return $data;
	}
}