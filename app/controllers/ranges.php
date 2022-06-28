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

		$this->add_main_button('Add Range', '/ranges/ranges_create');
		$this->add_main_button('Add List', '/ranges/list_create');
		$this->add_breadcrumb();
		$ranges_model = new \ranges_model();

		$app->mset([
			'content' => 'ranges.html',
			'page' => $page,
			'countries' => $this->app->get('countries'),
			'data' => $ranges_model->get_ranges_new() ,
			'data_list_groups' =>$ranges_model->get_number_list_groups(),
			'data_list_stats' => arr::map_id_nested($ranges_model->get_stats_lists(), 'group_id', 'status'),
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

	public function list_create(\Base $app, $params) {
		$this->list_edit(true);
	}

	public function list_edit($add) {
		$app = \Base::instance();

		$this->add_breadcrumb();

		if($app->get('GET.message') == 'created')
			$toast_message = __('mess.created');

		$params = $app->get('PARAMS');

		$ranges_model = new \ranges_model();

		$page_title = 'Add list number';
		$bread = 'Add list';

		if($add !== true) {
			if (($id = (int) $params['param1']) > 0)
				$ranges_model->model_number_list_groups->load(['id=?', $id]);
			else
				$this->render_error(500);

			$page_title = 'Edit list: ' . $ranges_model->model_number_list_groups->name;
			$bread = 'Edit list';

			if (!$ranges_model->model_number_list_groups->loaded())
				$error = ___('title.not_found');
		}

		if ($app->get('SERVER.REQUEST_METHOD') == 'POST' && !$error) {

			if ($app->get('POST.partner_id') > 0 && $app->get('POST.country_id') > 0 && $app->get('POST.name')) {
				$ranges_model->model_number_list_groups->name = $app->get('POST.name');
				$ranges_model->model_number_list_groups->status = validate::filter('0/1', $app->get('POST.status')) ? : 0;
				$ranges_model->model_number_list_groups->partner_id = validate::filter('int', $app->get('POST.partner_id')) ?: 0;
				$country_id = validate::filter('int', $app->get('POST.country_id')) ?: 0;

				$ranges_model->model_number_list_groups->save();

				if($add === true && $ranges_model->model_number_list_groups->id) {
					if($number_list = $app->get('POST.number_list'))
						$number_list_arr = explode(PHP_EOL, $number_list);

					if(!empty($number_list_arr)) {
						$numbers_arr = validate::filter_array('int_no_zero', $number_list_arr);
						$ranges_model->list_add_numbers($numbers_arr, $ranges_model->model_number_list_groups->id, $country_id);
					}

				}

				if($add === true && !$error)
					$app->reroute(\helpers\html::url('/ranges/list_edit/' . $ranges_model->model_number_list_groups->id . '?message=created'));
				$toast_message = ___('mess.saved');
			}
			else
				$error = 'Fill all fields';

			$this->add_breadcrumb($bread);

		}

		$numbers = $ranges_model->get_list_numbers($id);
		if(!empty($numbers[0]))
			$country_id = $numbers[0]['country_id'];

		$app->mset([
			'content' => 'list_edit.html',
			'error' => $error,
			'page_title' => $page_title,
			'toast_message' => $toast_message,
			'countries' => $this->app->get('countries'),
			'country_id' => $country_id,
			'numbers' => $numbers,
			'data' => $ranges_model->model_number_list_groups->cast(),
			'add' => $add,
		]);

		$this->render();
	}

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