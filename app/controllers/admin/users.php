<?php
namespace controllers\admin;

use helpers\auth;
use helpers\html;

class users extends \controllers\Controller {

	public function __construct(){
		auth::require_super();
		parent::__construct();
		$this->app->mset([
			'breadcrumb_admin' => true,
			'groups' => array_flip(auth::get_user_groups()),
			'clients' => (new \clients_model())->get_all_arr(),
		]);
		$this->add_breadcrumb(__('menu.admin'), '#');
	}

	public function index(\Base $app, $params) {
		$users = (new \users_model())->get_all();
		$app->mset([
			'content' => 'users.html',
			'data' => $users,
		]);
		$this->add_main_button(__('menu.add'), html::url('/admin/users/create'));

		$this->render();
	}

	public function create(\Base $app, $params) {
		$this->edit(true);
	}

	public function edit($add) {
		$app = \Base::instance();

		if($app->get('GET.message') == 'created')
			$toast_message = __('mess.created');

		$params = $app->get('PARAMS');

		$users = new \users_model();

		$page_title = ___('title.add_new');
		$bread = ___('title.add');

		if($add !== true) {
			if (($id = (int) $params['param1']) > 0)
				$users->model->load(['id = :id', ':id' => $id]);
			else
				$this->render_error(500);

			$page_title = __('users.edit') . ': ' . $users->model->username;
			$bread = ___('users.edit');

			if (!$users->model->loaded())
				$error = ___('title.not_found');
		}

		if(!$error) {
			if ($app->get('SERVER.REQUEST_METHOD') == 'POST' && $app->get('POST.username')) {
					$users->model->username = $app->get('POST.username');
					if($add === true or $app->get('POST.reset_password')) {
						$crypt = \Bcrypt::instance();
						$users->model->password = $crypt->hash($app->get('POST.password'));
					}
					else
						$users->model->password = $app->get('POST.password');
					if(in_array($app->get('POST.group'), auth::get_user_groups()))
						$users->model->group = $app->get('POST.group');
					else
						$error = 'Wrong group';
					$users->model->client_id = $app->get('POST.client_id');

					if(!$error)
						$users->model->save();

					if($add === true && !$error)
						$app->reroute(html::url('/admin/users/edit/' . $users->model->id . '?message=created'));
					$toast_message = ___('mess.saved');
			}

			$this->add_breadcrumb($bread);

			$app->mset([

				'data' => $users->model,
			]);
		}

		$app->mset([
			'content' => 'user_edit.html',
			'error' => $error,
			'page_title' => $page_title,
			'toast_message' => $toast_message,
		]);

		$this->render();
	}

	public function delete(\Base $app, $params) {
		$done = false;
		if(($user_id = (int) $params['param1'] )> 0) {

			if((new \users_model())->delete($user_id)) {
				$done = true;
			}
		}
		if($done == true)
			$this->render_ajax(['status' => 'success']);
		else
			$this->render_error('wrong id', true);
	}
}

