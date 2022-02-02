<?php
namespace controllers\admin;

use helpers\auth;
use helpers\html;

class clients extends \controllers\Controller {

	public function __construct(){
		parent::__construct();

		$this->app->mset([
			'breadcrumb_admin' => true,
		]);
		$this->add_breadcrumb(__('menu.admin'), '#');
	}

	public function check_rights() {
		auth::require_super();
	}

	public function index(\Base $app, $params) {
		$this->check_rights();

		$app->mset([
			'content' => 'clients.html',
			'clients' => \helpers\arr::map_id((new \clients_model())->get_all_arr(), 'id'),
		]);
		$this->add_main_button(__('forms.add'), '/admin/clients/create');

		$this->render();
	}

	public function create(\Base $app, $params) {
		$this->edit(true);
	}

	public function edit($add) {
		$this->check_rights();
		$app = \Base::instance();

		if($app->get('GET.message') == 'created')
			$toast_message = ___('mess.created');

		$params = $app->get('PARAMS');

		$clients = new \clients_model();

		$page_title = __('clients.add_new');
		$bread = __('clients.add_new');

		if($add !== true) {
			if (($id = (int) $params['param1']) > 0)
				$clients->model->load(['id = :id', ':id' => $id]);
			else
				$this->render_error(500);

			$page_title = ___('title.edit') . ': ' . $clients->model->name;
			$bread = ___('title.edit');

			if (!$clients->model->loaded())
				$error = __('title.not_found');
		}

		if(!$error) {
			if ($app->get('SERVER.REQUEST_METHOD') == 'POST' && $app->get('POST.name')) {
				$clients->model->name = $app->get('POST.name');
				$clients->model->type = (int) $app->get('POST.type');

				if(!$error)
					$clients->model->save();

				if($add === true && !$error) {
					$this->import_default_setting($clients->model->id);
					$app->reroute(html::url('/admin/clients/edit/' . $clients->model->id . '?message=created'));
				}
				$toast_message = ___('mess.saved');
			}

			$this->add_breadcrumb($bread);

			$app->mset([
				'data' => $clients->model,
			]);
		}

		$app->mset([
			'content' => 'client_edit.html',
			'error' => $error,
			'page_title' => $page_title,
			'toast_message' => $toast_message,
		]);

		$this->render();
	}

	public function delete(\Base $app, $params) {
		$this->check_rights();
		$done = false;
		if(($client_id = (int) $params['param1'] )> 0) {

			if((new \clients_model())->delete($client_id)) {
				$this->delete_settings($client_id);
				$done = true;
			}
		}
		if($done == true)
			$this->render_ajax(['status' => 'success']);
		else
			$this->render_error('wrong id', true);
	}

	protected function import_default_setting($client_id) {
		$settings = new \client_settings_model();
		foreach ($this->app->get('new_client_settings') as $set) {
			if(!$set['name'] or $set['optional'] == 1)
				continue;
			$settings->model->name = $set['name'];
			$settings->model->value = $set['value'];
			$settings->model->type = $set['type'];
			if($set['validation'])
				$settings->model->validation = $set['validation'];
			$settings->model->client_id = $client_id;
			$settings->model->save();
			$settings->model->reset();
		}
	}

	protected function delete_settings($client_id) {
		$settings = new \client_settings_model();
		return $settings->delet_all($client_id);
	}
}
