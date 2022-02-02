<?php

/*
a. clients
a. clients_management
u. connectors
u.d dial_history
u. number_storage
a. payments
u.d. servers
u.d. tasks
u.d. users
 */

class application {

	function __construct() {
	}

	public function import_client_settings($client_id) {
		$settings_model = new \client_settings_model();
		$this->app->set('settings', $settings = $settings_model->map_key_val($settings_model->get_all_arr($client_id)?:[], 'name', 'value'));

		if(!$this->app->get('settings.timezone'))
			$this->app->set('settings.timezone', $this->app->get('default_timezone'));

		date_default_timezone_set($this->app->get('settings.timezone'));
		return $settings;
	}
}
