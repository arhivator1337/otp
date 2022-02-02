<?php
namespace controllers;

class notifications extends \controllers\Controller {

	function __construct() {
		parent::__construct();
	}

	public function index(\Base $app, $params) {
		$model = new \models\messages_model();

		$app->mset([
			'content' => 'notifications.html',
			'data' => $model->get_all(),
			'types' => $this->app->get('messages.db_types'),
		]);

		$this->render();
	}
}