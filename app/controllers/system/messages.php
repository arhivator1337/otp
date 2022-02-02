<?php
namespace controllers\system;

use models\messages_model;

class messages extends \application {

	function __construct() {
		parent::__construct();
		$this->app = \Base::instance();
		$this->model = messages_model::instance();
		$this->db_types = array_flip($this->app->get('messages.db_types'));
	}

	public function add($type, $module, $message, $fatal = false) {
		if($this->db_types[$type])
			$_type = $this->db_types[$type];
		else {
			$_type = 1;
			$message = 'wrong type: ' . $type . '; ' . $message;
		}

		echo "<b>{$type}: {$module}: {$message}. Is fatal:{$fatal}</b><br>";

		$this->model->add_message($_type, $module, $message, $fatal);

		if($fatal == 1) {
			echo $message . ' fatal!';
			die;
		}

		return true;
	}
}