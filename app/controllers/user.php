<?php
namespace controllers;

use helpers\html;

class user extends \controllers\Controller {
	public function __construct() {

	}

	public function login(\Base $app, $params) {
		if ($app->get('SERVER.REQUEST_METHOD') == 'POST') {
			if (\helpers\auth::login_post()) {
				if ($app->get('GET.to'))
					$app->reroute($app->get('GET.to'));

				$app->reroute(html::url('/stats'));
				//$app->unload();
			} else
				$app->set('error', __('errors.auth_failed'));
		}
		$app->mset(['content' => 'login.html']);
		$this->render('layout_auth.html');
	}

	public function logout() {
		\helpers\auth::logout();
	}

	public function no_rights(\Base $app, $params) {
		echo __('errors.no_rights');
	}

	public function set_language(\Base $app, $params) {
		$params['param1'];
		$langs = $app->get('languages');
		if(in_array($params['param1'], $langs)) {
			$app->reroute($params['param1'] . '/tasks');
		}

	}
}