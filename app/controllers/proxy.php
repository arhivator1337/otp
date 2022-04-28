<?php
namespace controllers;

use helpers\arr;
use helpers\auth;
use helpers\html;
use helpers\validate;

class proxy extends \controllers\Controller {

	var $types = ['', 'option', 'array', 'timezone', 'hidden', 'admin_only', 'superadmin_only'];
	var $validation_types;

	function __construct() {
		parent::__construct();
//		$this->validation_types = array_merge_recursive([''], validate::$types);
//		$this->app->mset([
//			'is_superadmin' => auth::check_right(\helpers\auth::GROUP_SUPER_ADMIN),
//		]);
	}

	public function index(\Base $app, $params) {
		$app->mset([
			'content' => 'client_settings.html',
			'data' => (new \client_settings_model())->get_all_arr($this->client_id),
			'toast_message' => $app->get('GET.message') == 'updated' ? 'Settings applied': '',
			'is_validation_allowed' => \helpers\auth::check_right(\helpers\auth::GROUP_SUPER_ADMIN),
			'page_title' => __('menu.client_settings'),
		]);

		$this->add_breadcrumb(__('menu.admin'), '#');

		if(auth::check_right(auth::GROUP_SUPER_ADMIN))
			$this->add_main_button(__('forms.add'), '/client_settings/create_options');

		$this->add_main_button(__('forms.apply_changes'), '/client_settings/apply_changes?message=updated', ['class' => 'btn-warning']);
		$this->render();
	}

	public function create(\Base $app, $params) {
		$this->edit(true);
	}

	public function edit($add) {
		$app = \Base::instance();

		if($app->get('GET.message') == 'created')
			$toast_message = ___('mess.created');

		$params = $app->get('PARAMS');

		$settings = new \client_settings_model();

		$page_title = __('client_settings.add_new') ;
		$bread = __('client_settings.add_new');

		if($add !== true) {
			if (($id = (int) $params['param1']) > 0)
				$settings->model->load(['id = :id and client_id = :client_id', ':id' => $id, ':client_id' => $this->client_id]);
			else
				$this->render_error(500);

			$page_title = ___('client_settings.edit') . ': ' . $settings->model->name;
			$bread = ___('client_settings.edit');

			if (!$settings->model->loaded())
				$error = ___('errors.not_found');
		}

		if($add === true) {
			if(auth::check_right(auth::GROUP_SUPER_ADMIN) !== true)
				$error = ___('errors.not_found');
			$client_settings = arr::map_id($app->get('new_client_settings'), 'name');

			if(empty($client_settings[$params['param1']]))
				$error = ___('errors.not_found');
			else {
				$app->mset([
					'data' => (object) $client_settings[$params['param1']],
				]);

				$settings->model->client_id = $this->client_id;
			}
		}

		if($settings->model->type == 'admin_only' && auth::check_right(auth::GROUP_ADMIN) == false )
			$error = __('errors.admin_only');
		elseif($settings->model->type == 'superadmin_only' && auth::check_right(auth::GROUP_SUPER_ADMIN) == false )
			$error = __('errors.superadmin_only');

		if(!$error) {
			if ($app->get('SERVER.REQUEST_METHOD') == 'POST' && $app->get('POST.value') !== false) {
				$value = $this->special_handler($settings->model->type, $app->get('POST.value'), $settings->model->validation, 'save');
				if($value !== false)
					$settings->model->value = $value;
				else
					$error = __('errors.wrong_data', '`value`');

				if(in_array($app->get('POST.type'), $this->types) && ($add === true)) {
					$settings->model->name = $app->get('POST.name');
					$settings->model->type = $app->get('POST.type');
					if(in_array($app->get('POST.validation'), $this->validation_types))
						$settings->model->validation = $app->get('POST.validation');
				}

				if(!$error) {
					$settings->model->save();
					$app->set('saved', true);
					$toast_message = ___('mess.saved');
				}

				if($add === true && !$error)
					$app->reroute(html::url('/client_settings/edit/' . $settings->model->id . '?message=created'));
			}
			if($add !== true) {
				$app->mset([
					'data' => $settings->model,
				]);
			}
		}

		$this->add_breadcrumb($bread);

		$this->special_handler($settings->model->type);

		$app->mset([
			'content' => 'client_settings_edit.html',
			'error' => $error,
			'page_title' => $page_title,
			'toast_message' => $toast_message,
			'types' => $this->types,
			'add' => $add,
			'validation_types' => $this->validation_types,
			'can_change_type' => $app->get('user.group') >= auth::GROUP_SUPER_ADMIN ? true : false,
		]);

		$this->render();
	}

	function check(\Base $app, $params) {
		$model = new \proxy_model();
		if (($id = (int) $params['param1']) > 0) {

			$proxy = $model->get_proxy(null, null, null, $id);
			if(!empty($proxy[0])) {
				$proxy = $proxy[0];
				$proxy_data = html::check_proxy_real_ip($proxy);
				echo '<pre>';
				print_r($proxy);
				echo 'Proxy check 1:';
				print_r($proxy_data);
				echo '</pre>';
//				if(!empty($proxy_data['query'])) {
//					$url = "https://2ip.ua/ua/services/information-service/site-location?a=act&ip=" . $proxy_data['query'];
//				}
//				else
					$url = 'https://2ip.ua/ua';
				$data = html::curl2($url, ['user_agent' => 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.0.3705; .NET CLR 1.1.4322; Media Center PC 4.0)', 'timeout' => 1, 'proxy_ip' => $proxy['ip'], 'proxy_port' => $proxy['port'], 'proxy_login' => $proxy['login'], 'proxy_pass' => $proxy['pass']]);
				echo 'Proxy check 2:<br>';
				var_dump($data);
			}


		}

	}
}
